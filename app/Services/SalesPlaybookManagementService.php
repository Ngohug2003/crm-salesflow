<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PlaybookRepeatPolicy;
use App\Enums\SalesPlaybookStatus;
use App\Enums\SalesPlaybookStepType;
use App\Models\PipelineStage;
use App\Models\SalesPlaybook;
use App\Models\SalesPlaybookStep;
use App\Models\User;
use App\Repositories\Contracts\SalesPlaybookRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class SalesPlaybookManagementService
{
    public function __construct(
        private SalesPlaybookRepository $playbooks,
        private SystemAuditService $audit,
    ) {}

    /** @return Collection<int, SalesPlaybook> */
    public function list(User $actor): Collection
    {
        Gate::forUser($actor)->authorize('sales-playbook.view');

        return $this->playbooks->allVersions();
    }

    public function get(User $actor, int $id): SalesPlaybook
    {
        Gate::forUser($actor)->authorize('sales-playbook.view');

        return $this->playbooks->findOrFail($id);
    }

    /** @param list<array<string, mixed>> $steps */
    public function createDraft(
        User $actor,
        string $name,
        ?string $description,
        string $repeatPolicy,
        array $steps,
        ?int $draftStageId = null,
    ): SalesPlaybook {
        $this->authorizeManage($actor);
        $this->validateDefinition($name, $repeatPolicy, $steps, $draftStageId);

        return DB::transaction(function () use ($actor, $name, $description, $repeatPolicy, $steps, $draftStageId): SalesPlaybook {
            $code = $this->uniqueCode($name);
            $playbook = $this->playbooks->create([
                'code' => $code,
                'name' => trim($name),
                'description' => $this->nullableTrim($description),
                'version' => 1,
                'status' => SalesPlaybookStatus::Draft,
                'repeat_policy' => $repeatPolicy,
                'draft_pipeline_stage_id' => $draftStageId,
                'created_by' => $actor->id,
            ]);
            $playbook = $this->playbooks->replaceSteps($playbook, $this->normalizeSteps($steps));

            $this->audit->record($actor, $playbook, 'sales_playbook.created', "Tạo Sales Playbook '{$playbook->name}'", null, $playbook->toArray());

            return $playbook;
        });
    }

    /** @param list<array<string, mixed>> $steps */
    public function updateDraft(
        User $actor,
        int $id,
        string $name,
        ?string $description,
        string $repeatPolicy,
        array $steps,
        ?int $draftStageId = null,
    ): SalesPlaybook {
        $this->authorizeManage($actor);
        $this->validateDefinition($name, $repeatPolicy, $steps, $draftStageId);
        $playbook = $this->playbooks->findOrFail($id);
        $this->ensureDraft($playbook);

        return DB::transaction(function () use ($actor, $playbook, $name, $description, $repeatPolicy, $steps, $draftStageId): SalesPlaybook {
            $before = $playbook->load('steps')->toArray();
            $updated = $this->playbooks->update($playbook, [
                'name' => trim($name),
                'description' => $this->nullableTrim($description),
                'repeat_policy' => $repeatPolicy,
                'draft_pipeline_stage_id' => $draftStageId,
            ]);
            $updated = $this->playbooks->replaceSteps($updated, $this->normalizeSteps($steps));

            $this->audit->record($actor, $updated, 'sales_playbook.updated', "Cập nhật Sales Playbook '{$updated->name}'", $before, $updated->toArray());

            return $updated;
        });
    }

    public function publish(User $actor, int $id, ?int $stageId = null): SalesPlaybook
    {
        $this->authorizeManage($actor);
        $playbook = $this->playbooks->findOrFail($id);
        $this->ensureDraft($playbook);

        if ($playbook->steps->isEmpty()) {
            throw ValidationException::withMessages(['steps' => 'Playbook phải có ít nhất một bước trước khi phát hành.']);
        }

        return DB::transaction(function () use ($actor, $playbook, $stageId): SalesPlaybook {
            $targetStageId = $stageId ?? $playbook->draft_pipeline_stage_id;
            $published = $this->playbooks->update($playbook, [
                'status' => SalesPlaybookStatus::Published,
                'published_by' => $actor->id,
                'published_at' => now(),
            ]);

            if ($targetStageId !== null) {
                $stage = PipelineStage::query()->findOrFail($targetStageId);
                $this->playbooks->assignStage($stage, $published, $actor->id);
            }

            $this->audit->record(
                $actor,
                $published,
                'sales_playbook.published',
                "Phát hành Sales Playbook '{$published->name}' phiên bản {$published->version}",
                ['status' => SalesPlaybookStatus::Draft->value],
                ['status' => SalesPlaybookStatus::Published->value, 'pipeline_stage_id' => $targetStageId],
            );

            return $published->fresh(['steps', 'assignments.stage.pipeline']) ?? $published;
        });
    }

    public function createNextVersion(User $actor, int $id): SalesPlaybook
    {
        $this->authorizeManage($actor);
        $source = $this->playbooks->findOrFail($id);

        if (! in_array($source->status, [SalesPlaybookStatus::Published, SalesPlaybookStatus::Archived], true)) {
            throw ValidationException::withMessages(['playbook' => 'Chỉ có thể tạo phiên bản mới từ playbook đã phát hành hoặc đã lưu trữ.']);
        }

        return DB::transaction(function () use ($actor, $source): SalesPlaybook {
            $draftStageId = $source->assignments->isNotEmpty()
                ? $source->assignments->first()->pipeline_stage_id
                : $source->draft_pipeline_stage_id;
            $draft = $this->playbooks->create([
                'code' => $source->code,
                'name' => $source->name,
                'description' => $source->description,
                'version' => $this->playbooks->nextVersion($source->code),
                'status' => SalesPlaybookStatus::Draft,
                'repeat_policy' => $source->repeat_policy,
                'draft_pipeline_stage_id' => $draftStageId,
                'created_by' => $actor->id,
            ]);
            $steps = $source->steps->map(fn (SalesPlaybookStep $step): array => [
                'position' => $step->position,
                'type' => $step->type->value,
                'title' => $step->title,
                'instructions' => $step->instructions,
                'configuration' => $step->configuration,
                'is_required' => $step->is_required,
                'blocks_stage_exit' => $step->blocks_stage_exit,
                'due_business_days' => $step->due_business_days,
            ])->all();

            return $this->playbooks->replaceSteps($draft, $steps);
        });
    }

    public function archive(User $actor, int $id): SalesPlaybook
    {
        $this->authorizeManage($actor);
        $playbook = $this->playbooks->findOrFail($id);
        $before = ['status' => $playbook->status->value];
        $archived = $this->playbooks->update($playbook, ['status' => SalesPlaybookStatus::Archived]);
        $archived->assignments()->update(['is_active' => false]);

        $this->audit->record($actor, $archived, 'sales_playbook.archived', "Lưu trữ Sales Playbook '{$archived->name}'", $before, ['status' => SalesPlaybookStatus::Archived->value]);

        return $archived;
    }

    private function authorizeManage(User $actor): void
    {
        Gate::forUser($actor)->authorize('sales-playbook.manage');
    }

    private function ensureDraft(SalesPlaybook $playbook): void
    {
        if ($playbook->status !== SalesPlaybookStatus::Draft) {
            throw ValidationException::withMessages(['playbook' => 'Phiên bản đã phát hành hoặc lưu trữ không thể chỉnh sửa. Hãy tạo phiên bản mới.']);
        }
    }

    /** @param list<array<string, mixed>> $steps */
    private function validateDefinition(string $name, string $repeatPolicy, array $steps, ?int $draftStageId): void
    {
        if (trim($name) === '' || mb_strlen(trim($name)) > 255) {
            throw ValidationException::withMessages(['name' => 'Tên playbook là bắt buộc và không vượt quá 255 ký tự.']);
        }
        if (PlaybookRepeatPolicy::tryFrom($repeatPolicy) === null) {
            throw ValidationException::withMessages(['repeatPolicy' => 'Chính sách lặp không hợp lệ.']);
        }
        if ($draftStageId !== null && ! PipelineStage::query()->whereKey($draftStageId)->exists()) {
            throw ValidationException::withMessages(['stageId' => 'Giai đoạn được chọn không tồn tại.']);
        }

        foreach ($steps as $index => $step) {
            if (SalesPlaybookStepType::tryFrom((string) ($step['type'] ?? '')) === null) {
                throw ValidationException::withMessages(["steps.{$index}.type" => 'Loại bước không hợp lệ.']);
            }
            if (trim((string) ($step['title'] ?? '')) === '') {
                throw ValidationException::withMessages(["steps.{$index}.title" => 'Tiêu đề bước là bắt buộc.']);
            }
            if (($step['type'] ?? null) === SalesPlaybookStepType::RequiredField->value
                && ! in_array($step['field_name'] ?? null, $this->allowedOpportunityFields(), true)) {
                throw ValidationException::withMessages(["steps.{$index}.field_name" => 'Trường Opportunity bắt buộc không hợp lệ.']);
            }
        }
    }

    /** @param list<array<string, mixed>> $steps
     * @return list<array<string, mixed>>
     */
    private function normalizeSteps(array $steps): array
    {
        return collect($steps)->values()->map(function (array $step, int $index): array {
            $type = (string) $step['type'];
            $configuration = [];
            if ($type === SalesPlaybookStepType::RequiredField->value) {
                $configuration['field_name'] = $step['field_name'];
            }
            if ($type === SalesPlaybookStepType::Document->value && trim((string) ($step['document_url'] ?? '')) !== '') {
                $configuration['document_url'] = trim((string) $step['document_url']);
            }

            return [
                'position' => $index + 1,
                'type' => $type,
                'title' => trim((string) $step['title']),
                'instructions' => $this->nullableTrim($step['instructions'] ?? null),
                'configuration' => $configuration !== [] ? $configuration : null,
                'is_required' => (bool) ($step['is_required'] ?? false),
                'blocks_stage_exit' => (bool) ($step['blocks_stage_exit'] ?? false),
                'due_business_days' => isset($step['due_business_days']) && $step['due_business_days'] !== ''
                    ? max(0, min(365, (int) $step['due_business_days']))
                    : null,
            ];
        })->all();
    }

    /** @return list<string> */
    private function allowedOpportunityFields(): array
    {
        return ['amount', 'expected_close_date', 'company_id', 'contact_id', 'owner_id', 'notes'];
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? mb_substr($base, 0, 65) : 'playbook';
        $code = $base;
        $suffix = 2;

        while (SalesPlaybook::withTrashed()->where('code', $code)->exists()) {
            $code = "{$base}-{$suffix}";
            $suffix++;
        }

        return $code;
    }

    private function nullableTrim(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
