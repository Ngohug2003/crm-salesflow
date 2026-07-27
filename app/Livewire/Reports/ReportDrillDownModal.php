<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Models\User;
use App\Services\ReportDrillDownService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

final class ReportDrillDownModal extends Component
{
    public bool $showModal = false;

    public string $type = '';

    public ?int $departmentId = null;

    public ?int $ownerId = null;

    /** @var array{title: string, type: string, columns: array<int, string>, rows: array<int, array<string, mixed>>} */
    public array $drillDownData = [
        'title' => 'Chi tiết chỉ số',
        'type' => '',
        'columns' => [],
        'rows' => [],
    ];

    #[On('open-report-drilldown')]
    public function open(string $type = '', ?int $department_id = null, ?int $owner_id = null): void
    {
        $this->type = $type;
        $this->departmentId = $department_id;
        $this->ownerId = $owner_id;

        /** @var User $actor */
        $actor = Auth::user();
        /** @var ReportDrillDownService $service */
        $service = app(ReportDrillDownService::class);

        $this->drillDownData = $service->getDrillDownData(
            actor: $actor,
            type: $this->type,
            departmentId: $this->departmentId,
            ownerId: $this->ownerId
        );

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function render(): View
    {
        return view('livewire.reports.report-drill-down-modal');
    }
}
