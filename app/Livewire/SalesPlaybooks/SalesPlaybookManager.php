<?php

declare(strict_types=1);

namespace App\Livewire\SalesPlaybooks;

use App\Enums\PlaybookRepeatPolicy;
use App\Enums\SalesPlaybookStatus;
use App\Enums\SalesPlaybookStepType;
use App\Models\Pipeline;
use App\Models\SalesPlaybookStep;
use App\Models\User;
use App\Services\SalesPlaybookManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class SalesPlaybookManager extends Component
{
    public ?int $selectedId = null;

    public string $name = '';

    public string $description = '';

    public string $repeatPolicy = 'once';

    public string $stageId = '';

    /** @var list<array<string, mixed>> */
    public array $steps = [];

    public bool $showArchiveConfirmation = false;

    public function mount(): void
    {
        Gate::authorize('sales-playbook.manage');
        $this->resetEditor();
    }

    public function newPlaybook(): void
    {
        $this->resetEditor();
    }

    public function selectPlaybook(int $id): void
    {
        $playbook = $this->service()->get($this->actor(), $id);
        $this->selectedId = $playbook->id;
        $this->name = $playbook->name;
        $this->description = (string) $playbook->description;
        $this->repeatPolicy = $playbook->repeat_policy->value;
        $stageId = $playbook->assignments->isNotEmpty()
            ? $playbook->assignments->first()->pipeline_stage_id
            : $playbook->draft_pipeline_stage_id;
        $this->stageId = $stageId !== null ? (string) $stageId : '';
        $this->steps = $playbook->steps->map(fn (SalesPlaybookStep $step): array => [
            'type' => $step->type->value,
            'title' => $step->title,
            'instructions' => (string) $step->instructions,
            'field_name' => (string) ($step->configuration['field_name'] ?? ''),
            'document_url' => (string) ($step->configuration['document_url'] ?? ''),
            'is_required' => $step->is_required,
            'blocks_stage_exit' => $step->blocks_stage_exit,
            'due_business_days' => $step->due_business_days !== null ? (string) $step->due_business_days : '',
        ])->all();
        $this->resetValidation();
    }

    public function addStep(): void
    {
        $this->steps[] = $this->emptyStep();
    }

    public function removeStep(int $index): void
    {
        if (! array_key_exists($index, $this->steps)) {
            return;
        }

        unset($this->steps[$index]);
        $this->steps = array_values($this->steps);
    }

    public function moveStep(int $index, int $direction): void
    {
        $target = $index + $direction;
        if (! isset($this->steps[$index], $this->steps[$target])) {
            return;
        }

        [$this->steps[$index], $this->steps[$target]] = [$this->steps[$target], $this->steps[$index]];
    }

    public function save(): void
    {
        $this->validateDefinition();
        $service = $this->service();
        $selectedStageId = $this->stageId;

        if ($this->selectedId === null) {
            $playbook = $service->createDraft(
                $this->actor(),
                $this->name,
                $this->description,
                $this->repeatPolicy,
                $this->steps,
                $selectedStageId !== '' ? (int) $selectedStageId : null,
            );
        } else {
            $playbook = $service->updateDraft(
                $this->actor(),
                $this->selectedId,
                $this->name,
                $this->description,
                $this->repeatPolicy,
                $this->steps,
                $selectedStageId !== '' ? (int) $selectedStageId : null,
            );
        }

        $this->selectPlaybook($playbook->id);
        $this->stageId = $selectedStageId;
        session()->flash('success', 'Đã lưu bản nháp Sales Playbook.');
    }

    public function publish(): void
    {
        $selectedStageId = $this->stageId;

        $this->save();

        $playbook = $this->service()->publish(
            $this->actor(),
            (int) $this->selectedId,
            $selectedStageId !== '' ? (int) $selectedStageId : null,
        );
        $this->selectPlaybook($playbook->id);
        session()->flash('success', "Đã phát hành phiên bản {$playbook->version}.");
    }

    public function createNextVersion(): void
    {
        if ($this->selectedId === null) {
            return;
        }

        $selectedStageId = $this->stageId;
        $draft = $this->service()->createNextVersion($this->actor(), $this->selectedId);
        $this->selectPlaybook($draft->id);
        $this->stageId = $selectedStageId;
        session()->flash('success', "Đã tạo bản nháp phiên bản {$draft->version}.");
    }

    public function archive(): void
    {
        if ($this->selectedId === null) {
            return;
        }

        $this->service()->archive($this->actor(), $this->selectedId);
        $this->showArchiveConfirmation = false;
        $this->resetEditor();
        session()->flash('success', 'Đã lưu trữ playbook và ngừng assignment đang hoạt động.');
    }

    public function render(): View
    {
        $playbooks = $this->service()->list($this->actor());
        $selected = $this->selectedId !== null ? $playbooks->firstWhere('id', $this->selectedId) : null;
        $pipelines = Pipeline::query()->active()->with('stages')->orderBy('name')->get();

        return view('livewire.sales-playbooks.sales-playbook-manager', [
            'playbooks' => $playbooks,
            'selected' => $selected,
            'pipelines' => $pipelines,
            'stepTypes' => SalesPlaybookStepType::cases(),
            'repeatPolicies' => PlaybookRepeatPolicy::cases(),
            'fieldOptions' => $this->fieldOptions(),
            'isDraft' => $selected === null || $selected->status === SalesPlaybookStatus::Draft,
        ]);
    }

    private function resetEditor(): void
    {
        $this->selectedId = null;
        $this->name = '';
        $this->description = '';
        $this->repeatPolicy = PlaybookRepeatPolicy::Once->value;
        $this->stageId = '';
        $this->steps = [$this->emptyStep()];
        $this->resetValidation();
    }

    /** @return array<string, mixed> */
    private function emptyStep(): array
    {
        return [
            'type' => SalesPlaybookStepType::Checklist->value,
            'title' => '',
            'instructions' => '',
            'field_name' => '',
            'document_url' => '',
            'is_required' => true,
            'blocks_stage_exit' => false,
            'due_business_days' => '',
        ];
    }

    private function validateDefinition(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'repeatPolicy' => ['required', 'in:once,every_entry,manual'],
            'stageId' => ['nullable', 'integer', 'exists:pipeline_stages,id'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.type' => ['required', 'in:guidance,question,required_field,checklist,task,reminder,document'],
            'steps.*.title' => ['required', 'string', 'max:255'],
            'steps.*.instructions' => ['nullable', 'string', 'max:5000'],
            'steps.*.due_business_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ], [
            'name.required' => 'Vui lòng nhập tên playbook.',
            'steps.min' => 'Playbook phải có ít nhất một bước.',
            'steps.*.title.required' => 'Mỗi bước phải có tiêu đề.',
        ]);
    }

    /** @return array<string, string> */
    private function fieldOptions(): array
    {
        return [
            'amount' => 'Giá trị cơ hội',
            'expected_close_date' => 'Ngày đóng dự kiến',
            'company_id' => 'Doanh nghiệp',
            'contact_id' => 'Người liên hệ',
            'owner_id' => 'Người phụ trách',
            'notes' => 'Ghi chú',
        ];
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    private function service(): SalesPlaybookManagementService
    {
        return app(SalesPlaybookManagementService::class);
    }
}
