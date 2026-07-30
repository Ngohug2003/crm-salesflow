<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\OpportunityFilterData;
use App\Enums\ForecastCategory;
use App\Jobs\ActivateOpportunityPlaybookJob;
use App\Models\Opportunity;
use App\Models\PipelineStage;
use App\Models\User;
use App\Repositories\Contracts\OpportunityRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class OpportunityManagementService
{
    public function __construct(
        private OpportunityRepository $opportunities,
        private SystemAuditService $audit,
        private OpportunityPlaybookService $playbooks,
    ) {}

    /** @return LengthAwarePaginator<int, Opportunity> */
    public function list(User $actor, OpportunityFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        Gate::forUser($actor)->authorize('viewAny', Opportunity::class);

        return $this->opportunities->paginateVisible($actor, $filters, $perPage);
    }

    public function get(User $actor, int $id): Opportunity
    {
        $opportunity = $this->opportunities->findVisibleOrFail($actor, $id);
        Gate::forUser($actor)->authorize('view', $opportunity);

        return $opportunity;
    }

    /** @return array{total_count: int, total_amount: float, total_weighted_value: float} */
    public function summarize(User $actor, OpportunityFilterData $filters): array
    {
        Gate::forUser($actor)->authorize('viewAny', Opportunity::class);

        return $this->opportunities->summarizeVisible($actor, $filters);
    }

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): Opportunity
    {
        Gate::forUser($actor)->authorize('create', Opportunity::class);

        return DB::transaction(function () use ($actor, $data): Opportunity {
            $ownerId = isset($data['owner_id']) && $data['owner_id'] !== '' ? (int) $data['owner_id'] : null;

            if ($ownerId === null && ! $actor->hasAnyRole(['super-admin', 'admin'])) {
                $ownerId = $actor->getKey();
            }

            $departmentId = null;
            if ($ownerId !== null) {
                $ownerUser = User::query()->find($ownerId);
                $departmentId = $ownerUser?->department_id;
            }

            $code = isset($data['code']) && trim((string) $data['code']) !== ''
                ? trim((string) $data['code'])
                : $this->generateUniqueCode();

            $stageId = (int) $data['stage_id'];
            $stage = PipelineStage::query()->findOrFail($stageId);
            $this->playbooks->ensureCanEnterStage($actor, $stageId);

            $forecastCategory = $data['forecast_category'] ?? ($stage->is_won || $stage->is_lost ? ForecastCategory::Closed : ForecastCategory::Pipeline);

            $payload = array_merge($data, [
                'code' => $code,
                'is_won' => $stage->is_won,
                'is_lost' => $stage->is_lost,
                'forecast_category' => $forecastCategory,
                'owner_id' => $ownerId,
                'department_id' => $departmentId,
                'created_by' => $actor->getKey(),
                'updated_by' => $actor->getKey(),
            ]);

            $opportunity = $this->opportunities->create($payload);

            $this->audit->record(
                $actor,
                $opportunity,
                'created',
                'Tạo mới Cơ hội bán hàng',
                null,
                $this->snapshot($opportunity),
            );

            ActivateOpportunityPlaybookJob::dispatch($actor->id, $opportunity->id, $stageId, 0)->afterCommit();

            return $opportunity;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, int $id, array $data): Opportunity
    {
        return DB::transaction(function () use ($actor, $id, $data): Opportunity {
            $opportunity = $this->opportunities->findVisibleForUpdateOrFail($actor, $id);
            Gate::forUser($actor)->authorize('update', $opportunity);

            $oldSnapshot = $this->snapshot($opportunity);

            $ownerId = isset($data['owner_id']) && $data['owner_id'] !== '' ? (int) $data['owner_id'] : $opportunity->owner_id;
            $departmentId = $opportunity->department_id;

            if ($ownerId !== $opportunity->owner_id && $ownerId !== null) {
                $ownerUser = User::query()->find($ownerId);
                $departmentId = $ownerUser?->department_id;
            }

            $stageId = isset($data['stage_id']) ? (int) $data['stage_id'] : $opportunity->stage_id;
            if ($stageId !== $opportunity->stage_id) {
                throw ValidationException::withMessages([
                    'stage_id' => 'Hãy chuyển giai đoạn tại thanh tiến trình hoặc Kanban để hệ thống kiểm tra playbook và lưu lịch sử.',
                ]);
            }
            $stage = PipelineStage::query()->findOrFail($stageId);

            $payload = array_merge($data, [
                'is_won' => $stage->is_won,
                'is_lost' => $stage->is_lost,
                'owner_id' => $ownerId,
                'department_id' => $departmentId,
                'updated_by' => $actor->getKey(),
            ]);

            $updatedOpportunity = $this->opportunities->update($opportunity, $payload);

            $this->audit->record(
                $actor,
                $updatedOpportunity,
                'updated',
                'Cập nhật Cơ hội bán hàng',
                $oldSnapshot,
                $this->snapshot($updatedOpportunity),
            );

            return $updatedOpportunity;
        });
    }

    public function delete(User $actor, int $id): Opportunity
    {
        return DB::transaction(function () use ($actor, $id): Opportunity {
            $opportunity = $this->opportunities->findVisibleForUpdateOrFail($actor, $id);
            Gate::forUser($actor)->authorize('delete', $opportunity);

            $oldSnapshot = $this->snapshot($opportunity);
            $deletedOpportunity = $this->opportunities->softDelete($opportunity);

            $this->audit->record(
                $actor,
                $deletedOpportunity,
                'deleted',
                'Xóa Cơ hội bán hàng',
                $oldSnapshot,
                [...$oldSnapshot, 'deleted_at' => $deletedOpportunity->deleted_at?->toISOString()],
            );

            return $deletedOpportunity;
        });
    }

    private function generateUniqueCode(): string
    {
        $year = date('Y');
        $latest = Opportunity::withTrashed()
            ->where('code', 'like', "OPP-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($latest !== null && preg_match('/OPP-\d{4}-(\d+)/', $latest->code, $matches) === 1) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('OPP-%s-%05d', $year, $sequence);
    }

    /** @return array<string, mixed> */
    private function snapshot(Opportunity $opportunity): array
    {
        return [
            'id' => $opportunity->getKey(),
            'code' => $opportunity->code,
            'title' => $opportunity->title,
            'amount' => (float) $opportunity->amount,
            'pipeline_id' => $opportunity->pipeline_id,
            'stage_id' => $opportunity->stage_id,
            'company_id' => $opportunity->company_id,
            'contact_id' => $opportunity->contact_id,
            'is_won' => $opportunity->is_won,
            'is_lost' => $opportunity->is_lost,
            'owner_id' => $opportunity->owner_id,
            'department_id' => $opportunity->department_id,
        ];
    }
}
