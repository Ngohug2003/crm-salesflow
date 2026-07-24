<?php

declare(strict_types=1);

namespace App\Livewire\Pipelines;

use App\Models\Pipeline;
use App\Models\User;
use App\Services\PipelineManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class PipelineEditor extends Component
{
    public ?int $pipelineId = null;

    public string $name = '';

    public string $code = '';

    public string $description = '';

    public bool $is_default = false;

    public bool $is_active = true;

    /** @var list<array{id?: int, name: string, code: string, description: string, position: int, probability: int, color: string, is_won: bool, is_lost: bool, is_system: bool}> */
    public array $stages = [];

    public function mount(?int $pipelineId = null): void
    {
        /** @var User $actor */
        $actor = Auth::user();

        if ($pipelineId !== null) {
            $this->pipelineId = $pipelineId;
            /** @var PipelineManagementService $service */
            $service = app(PipelineManagementService::class);
            $pipeline = $service->get($actor, $pipelineId);

            Gate::forUser($actor)->authorize('update', $pipeline);

            $this->name = $pipeline->name;
            $this->code = $pipeline->code;
            $this->description = (string) $pipeline->description;
            $this->is_default = $pipeline->is_default;
            $this->is_active = $pipeline->is_active;

            foreach ($pipeline->stages as $stg) {
                $this->stages[] = [
                    'id' => $stg->id,
                    'name' => $stg->name,
                    'code' => $stg->code,
                    'description' => (string) $stg->description,
                    'position' => $stg->position,
                    'probability' => $stg->probability,
                    'color' => $stg->color,
                    'is_won' => $stg->is_won,
                    'is_lost' => $stg->is_lost,
                    'is_system' => $stg->is_system,
                ];
            }
        } else {
            Gate::forUser($actor)->authorize('create', Pipeline::class);

            // Default 3 starter stages for a new pipeline
            $this->stages = [
                ['name' => 'Tiếp cận', 'code' => 'tiep-can', 'description' => '', 'position' => 1, 'probability' => 20, 'color' => '#3B82F6', 'is_won' => false, 'is_lost' => false, 'is_system' => false],
                ['name' => 'Báo giá', 'code' => 'bao-gia', 'description' => '', 'position' => 2, 'probability' => 50, 'color' => '#8B5CF6', 'is_won' => false, 'is_lost' => false, 'is_system' => false],
                ['name' => 'Thành công (Won)', 'code' => 'closed-won', 'description' => '', 'position' => 3, 'probability' => 100, 'color' => '#10B981', 'is_won' => true, 'is_lost' => false, 'is_system' => true],
                ['name' => 'Thất bại (Lost)', 'code' => 'closed-lost', 'description' => '', 'position' => 4, 'probability' => 0, 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true, 'is_system' => true],
            ];
        }
    }

    public function updatedName(string $value): void
    {
        if ($this->pipelineId === null && trim($this->code) === '') {
            $this->code = Str::slug(trim($value));
        }
    }

    public function addStage(): void
    {
        $nextPos = count($this->stages) + 1;
        $this->stages[] = [
            'name' => 'Giai đoạn mới '.$nextPos,
            'code' => 'giai-doan-moi-'.$nextPos,
            'description' => '',
            'position' => $nextPos,
            'probability' => 50,
            'color' => '#64748B',
            'is_won' => false,
            'is_lost' => false,
            'is_system' => false,
        ];
    }

    public function moveStageUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->stages[$index])) {
            return;
        }

        $prevIndex = $index - 1;
        $temp = $this->stages[$prevIndex];
        $this->stages[$prevIndex] = $this->stages[$index];
        $this->stages[$index] = $temp;

        $this->reindexPositions();
    }

    public function moveStageDown(int $index): void
    {
        if ($index >= count($this->stages) - 1 || ! isset($this->stages[$index])) {
            return;
        }

        $nextIndex = $index + 1;
        $temp = $this->stages[$nextIndex];
        $this->stages[$nextIndex] = $this->stages[$index];
        $this->stages[$index] = $temp;

        $this->reindexPositions();
    }

    public function reorderStages(int $fromIndex, int $toIndex): void
    {
        if (! isset($this->stages[$fromIndex]) || ! isset($this->stages[$toIndex])) {
            return;
        }

        $movedItem = array_splice($this->stages, $fromIndex, 1)[0];
        array_splice($this->stages, $toIndex, 0, [$movedItem]);

        $this->reindexPositions();
    }

    public function removeStage(int $index): void
    {
        if (! isset($this->stages[$index])) {
            return;
        }

        if (! empty($this->stages[$index]['is_system'])) {
            $this->addError('stages', "Không thể xóa giai đoạn hệ thống [{$this->stages[$index]['name']}].");

            return;
        }

        array_splice($this->stages, $index, 1);
        $this->reindexPositions();
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
            'stages' => ['required', 'array', 'min:1'],
            'stages.*.name' => ['required', 'string', 'max:255'],
            'stages.*.probability' => ['required', 'integer', 'min:0', 'max:100'],
            'stages.*.color' => ['required', 'string', 'max:20'],
        ]);

        /** @var User $actor */
        $actor = Auth::user();
        /** @var PipelineManagementService $service */
        $service = app(PipelineManagementService::class);

        $pipelineData = [
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
        ];

        if ($this->pipelineId !== null) {
            $pipeline = $service->update($actor, $this->pipelineId, $pipelineData, $this->stages);
            session()->flash('message', "Đã cập nhật quy trình '{$pipeline->name}'.");
        } else {
            $pipeline = $service->create($actor, $pipelineData, $this->stages);
            session()->flash('message', "Đã tạo mới quy trình '{$pipeline->name}'.");
        }

        $this->redirect(route('pipelines.show', $pipeline->id), navigate: true);
    }

    private function reindexPositions(): void
    {
        $updated = [];
        foreach ($this->stages as $i => $stg) {
            $stg['position'] = $i + 1;
            $updated[] = $stg;
        }
        $this->stages = $updated;
    }

    public function render(): View
    {
        return view('livewire.pipelines.pipeline-editor');
    }
}
