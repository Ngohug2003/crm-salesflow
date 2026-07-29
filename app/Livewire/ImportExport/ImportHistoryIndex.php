<?php

declare(strict_types=1);

namespace App\Livewire\ImportExport;

use App\Models\User;
use App\Services\ImportExport\ImportHistoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class ImportHistoryIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $type = 'all';

    #[Url(except: 'all')]
    public string $status = 'all';

    public ?int $selectedBatchId = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'type', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function openErrorModal(int $batchId): void
    {
        $this->selectedBatchId = $batchId;
    }

    public function closeErrorModal(): void
    {
        $this->selectedBatchId = null;
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'type', 'status');
        $this->resetPage();
    }

    public function render(ImportHistoryService $service): View
    {
        /** @var User $user */
        $user = Auth::user();

        $batches = $service->getPaginatedBatches($user, [
            'search' => $this->search,
            'type' => $this->type,
            'status' => $this->status,
        ]);

        $stats = $service->getImportSummaryStats($user);

        $selectedBatch = $this->selectedBatchId !== null
            ? $service->findVisibleBatch($user, $this->selectedBatchId)
            : null;

        return view('livewire.import-export.import-history-index', [
            'batches' => $batches,
            'stats' => $stats,
            'selectedBatch' => $selectedBatch,
        ]);
    }
}
