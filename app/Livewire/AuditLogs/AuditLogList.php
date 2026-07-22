<?php

declare(strict_types=1);

namespace App\Livewire\AuditLogs;

use App\Data\AuditLogFilters;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

final class AuditLogList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $module = 'all';

    #[Url(except: 'all')]
    public string $event = 'all';

    #[Url(except: 'all')]
    public string $actor = 'all';

    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    #[Url(as: 'view', except: 'table')]
    public string $viewMode = 'table';

    public function mount(): void
    {
        Gate::authorize('viewAny', Activity::class);

        if (! in_array($this->viewMode, ['table', 'log'], true)) {
            $this->viewMode = 'table';
        }
    }

    /** @return LengthAwarePaginator<int, Activity> */
    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', Activity::class);

        return $this->service()->paginate(new AuditLogFilters(
            $this->search, $this->module, $this->event, $this->actor, $this->dateFrom, $this->dateTo,
        ));
    }

    /** @return array{modules: list<string>, events: list<string>, actors: Collection<int, User>} */
    #[Computed]
    public function options(): array
    {
        return $this->service()->options();
    }

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    public function updatedViewMode(string $value): void
    {
        if (! in_array($value, ['table', 'log'], true)) {
            $this->viewMode = 'table';
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'module', 'event', 'actor', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.audit-logs.audit-log-list');
    }

    private function service(): AuditLogService
    {
        return app(AuditLogService::class);
    }
}
