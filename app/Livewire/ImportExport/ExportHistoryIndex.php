<?php

declare(strict_types=1);

namespace App\Livewire\ImportExport;

use App\Models\User;
use App\Services\ImportExport\ExportHistoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class ExportHistoryIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $type = 'all';

    #[Url(except: 'all')]
    public string $status = 'all';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'type', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'type', 'status');
        $this->resetPage();
    }

    public function render(ExportHistoryService $service): View
    {
        /** @var User $user */
        $user = Auth::user();

        $batches = $service->getPaginatedBatches($user, [
            'search' => $this->search,
            'type' => $this->type,
            'status' => $this->status,
        ]);

        $stats = $service->getExportSummaryStats($user);

        return view('livewire.import-export.export-history-index', [
            'batches' => $batches,
            'stats' => $stats,
            'service' => $service,
        ]);
    }
}
