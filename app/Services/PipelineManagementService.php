<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\PipelineFilterData;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Repositories\Contracts\PipelineRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class PipelineManagementService
{
    public function __construct(
        private PipelineRepository $pipelines,
        private SystemAuditService $audit,
    ) {}

    /** @return LengthAwarePaginator<int, Pipeline> */
    public function list(User $actor, PipelineFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        Gate::forUser($actor)->authorize('viewAny', Pipeline::class);

        return $this->pipelines->paginateVisible($actor, $filters, $perPage);
    }

    public function get(User $actor, int $id): Pipeline
    {
        $pipeline = $this->pipelines->findVisibleOrFail($actor, $id);
        Gate::forUser($actor)->authorize('view', $pipeline);

        return $pipeline;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{id?: int, name: string, code?: string, description?: string, position?: int, probability?: int, color?: string, is_won?: bool, is_lost?: bool, is_system?: bool}>  $stages
     */
    public function create(User $actor, array $data, array $stages = []): Pipeline
    {
        Gate::forUser($actor)->authorize('create', Pipeline::class);

        return DB::transaction(function () use ($actor, $data, $stages): Pipeline {
            $ownerId = isset($data['owner_id']) && $data['owner_id'] !== '' ? (int) $data['owner_id'] : null;

            if ($ownerId === null && ! $actor->hasAnyRole(['super-admin', 'admin'])) {
                $ownerId = $actor->getKey();
            }

            $departmentId = null;
            if ($ownerId !== null) {
                $ownerUser = User::query()->find($ownerId);
                $departmentId = $ownerUser?->department_id;
            }

            $isDefault = (bool) ($data['is_default'] ?? false);
            if ($isDefault) {
                $this->pipelines->resetDefaultExcept();
            }

            $code = isset($data['code']) && trim((string) $data['code']) !== ''
                ? Str::slug(trim((string) $data['code']))
                : Str::slug(trim((string) $data['name']));

            $payload = array_merge($data, [
                'code' => $code,
                'is_default' => $isDefault,
                'owner_id' => $ownerId,
                'department_id' => $departmentId,
                'created_by' => $actor->getKey(),
                'updated_by' => $actor->getKey(),
            ]);

            $pipeline = $this->pipelines->create($payload);

            if ($stages !== []) {
                $this->syncStages($pipeline, $stages);
            }

            $this->audit->record(
                $actor,
                $pipeline,
                'created',
                'Tạo mới Quy trình bán hàng',
                null,
                $this->snapshot($pipeline),
            );

            return $pipeline;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{id?: int, name: string, code?: string, description?: string, position?: int, probability?: int, color?: string, is_won?: bool, is_lost?: bool, is_system?: bool}>  $stages
     */
    public function update(User $actor, int $id, array $data, array $stages = []): Pipeline
    {
        return DB::transaction(function () use ($actor, $id, $data, $stages): Pipeline {
            $pipeline = $this->pipelines->findVisibleForUpdateOrFail($actor, $id);
            Gate::forUser($actor)->authorize('update', $pipeline);

            $oldSnapshot = $this->snapshot($pipeline);

            $ownerId = isset($data['owner_id']) && $data['owner_id'] !== '' ? (int) $data['owner_id'] : $pipeline->owner_id;
            $departmentId = $pipeline->department_id;

            if ($ownerId !== $pipeline->owner_id && $ownerId !== null) {
                $ownerUser = User::query()->find($ownerId);
                $departmentId = $ownerUser?->department_id;
            }

            $isDefault = array_key_exists('is_default', $data) ? (bool) $data['is_default'] : $pipeline->is_default;
            if ($isDefault && ! $pipeline->is_default) {
                $this->pipelines->resetDefaultExcept($pipeline->id);
            }

            $code = isset($data['code']) && trim((string) $data['code']) !== ''
                ? Str::slug(trim((string) $data['code']))
                : $pipeline->code;

            $payload = array_merge($data, [
                'code' => $code,
                'is_default' => $isDefault,
                'owner_id' => $ownerId,
                'department_id' => $departmentId,
                'updated_by' => $actor->getKey(),
            ]);

            $updatedPipeline = $this->pipelines->update($pipeline, $payload);

            if ($stages !== []) {
                $this->syncStages($updatedPipeline, $stages);
            }

            $this->audit->record(
                $actor,
                $updatedPipeline,
                'updated',
                'Cập nhật Quy trình bán hàng',
                $oldSnapshot,
                $this->snapshot($updatedPipeline),
            );

            return $updatedPipeline;
        });
    }

    public function delete(User $actor, int $id): Pipeline
    {
        return DB::transaction(function () use ($actor, $id): Pipeline {
            $pipeline = $this->pipelines->findVisibleForUpdateOrFail($actor, $id);
            Gate::forUser($actor)->authorize('delete', $pipeline);

            $oldSnapshot = $this->snapshot($pipeline);
            $deletedPipeline = $this->pipelines->softDelete($pipeline);

            $this->audit->record(
                $actor,
                $deletedPipeline,
                'deleted',
                'Xóa Quy trình bán hàng',
                $oldSnapshot,
                [...$oldSnapshot, 'deleted_at' => $deletedPipeline->deleted_at?->toISOString()],
            );

            return $deletedPipeline;
        });
    }

    /**
     * @param  list<array{id?: int, name: string, code?: string, description?: string, position?: int, probability?: int, color?: string, is_won?: bool, is_lost?: bool, is_system?: bool}>  $stages
     */
    private function syncStages(Pipeline $pipeline, array $stages): void
    {
        $existingStageIds = $pipeline->stages()->pluck('id')->all();
        $keepStageIds = [];

        foreach ($stages as $index => $stageData) {
            $stageId = isset($stageData['id']) ? (int) $stageData['id'] : null;
            $name = trim($stageData['name']);
            $code = isset($stageData['code']) && trim((string) $stageData['code']) !== ''
                ? Str::slug(trim((string) $stageData['code']))
                : Str::slug($name);

            $payload = [
                'pipeline_id' => $pipeline->id,
                'name' => $name,
                'code' => $code,
                'description' => isset($stageData['description']) ? trim((string) $stageData['description']) : null,
                'position' => isset($stageData['position']) ? (int) $stageData['position'] : ($index + 1),
                'probability' => isset($stageData['probability']) ? (int) $stageData['probability'] : 0,
                'color' => isset($stageData['color']) && trim((string) $stageData['color']) !== '' ? trim((string) $stageData['color']) : '#3B82F6',
                'is_won' => (bool) ($stageData['is_won'] ?? false),
                'is_lost' => (bool) ($stageData['is_lost'] ?? false),
                'is_system' => (bool) ($stageData['is_system'] ?? false),
            ];

            if ($stageId !== null && in_array($stageId, $existingStageIds, true)) {
                $stageModel = PipelineStage::query()->find($stageId);
                if ($stageModel !== null) {
                    $stageModel->update($payload);
                    $keepStageIds[] = $stageId;
                }
            } else {
                $newStage = PipelineStage::query()->create($payload);
                $keepStageIds[] = $newStage->id;
            }
        }

        // Check stages to be deleted
        $deleteStageIds = array_diff($existingStageIds, $keepStageIds);
        foreach ($deleteStageIds as $toDeleteId) {
            $stage = PipelineStage::query()->find($toDeleteId);
            if ($stage !== null) {
                if ($stage->is_system) {
                    throw new InvalidArgumentException("Không thể xóa giai đoạn hệ thống [{$stage->name}].");
                }
                $stage->delete();
            }
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(Pipeline $pipeline): array
    {
        return [
            'id' => $pipeline->getKey(),
            'name' => $pipeline->name,
            'code' => $pipeline->code,
            'description' => $pipeline->description,
            'is_default' => $pipeline->is_default,
            'is_active' => $pipeline->is_active,
            'owner_id' => $pipeline->owner_id,
            'department_id' => $pipeline->department_id,
            'stages_count' => $pipeline->stages()->count(),
        ];
    }
}
