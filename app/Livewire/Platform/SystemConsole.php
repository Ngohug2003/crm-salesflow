<?php

declare(strict_types=1);

namespace App\Livewire\Platform;

use App\Data\SystemLogFilters;
use App\Services\Platform\EnvironmentReadinessCheckService;
use App\Services\Platform\SystemHealthCheckService;
use App\Services\SystemLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
final class SystemConsole extends Component
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $level = 'all';

    #[Url(except: 'all')]
    public string $module = 'all';

    #[Url(except: 100)]
    public int $limit = 100;

    public bool $paused = false;

    /** @var list<array{id: string, timestamp: string, level: string, module: string, action: string, status_code: int|null, user_id: int|null, duration_ms: float|null, request_id: string, event: string, line: string}> */
    public array $entries = [];

    /** @var list<string> */
    public array $modules = [];

    public ?string $source = null;

    public ?string $lastRefreshedAt = null;

    public ?string $errorMessage = null;

    public ?array $healthReport = null;

    public ?array $envChecklist = null;

    public function mount(): void
    {
        Gate::authorize('system-console.view');
        $this->normalizeFilters();
        $this->refreshLogs();
        $this->refreshHealthCheck();
        $this->refreshEnvChecklist();
    }

    public function refreshHealthCheck(): void
    {
        Gate::authorize('system-console.view');
        $this->healthReport = app(SystemHealthCheckService::class)->checkAll();
    }

    public function refreshEnvChecklist(): void
    {
        Gate::authorize('system-console.view');
        $this->envChecklist = app(EnvironmentReadinessCheckService::class)->checkAll();
    }

    public function updated(string $property): void
    {
        if (! in_array($property, ['search', 'level', 'module', 'limit'], true)) {
            return;
        }

        $this->normalizeFilters();
        $this->refreshLogs();
    }

    public function refreshLogs(): void
    {
        Gate::authorize('system-console.view');

        try {
            $result = app(SystemLogService::class)->read(new SystemLogFilters(
                $this->search,
                $this->level,
                $this->module,
                $this->limit,
            ));

            $this->entries = $result['entries'];
            $this->modules = $result['modules'];
            $this->source = $result['source'];
            $this->lastRefreshedAt = now()
                ->timezone((string) config('crm.display_timezone'))
                ->format('H:i:s');
            $this->errorMessage = null;
        } catch (Throwable) {
            $this->entries = [];
            $this->modules = [];
            $this->source = null;
            $this->errorMessage = 'Không thể đọc nhật ký lúc này. Vui lòng thử lại.';
        }
    }

    public function togglePolling(): void
    {
        Gate::authorize('system-console.view');
        $this->paused = ! $this->paused;

        if (! $this->paused) {
            $this->refreshLogs();
        }
    }

    public function clearFilters(): void
    {
        Gate::authorize('system-console.view');
        $this->reset('search', 'level', 'module');
        $this->refreshLogs();
    }

    public function render(): View
    {
        Gate::authorize('system-console.view');

        return view('livewire.platform.system-console');
    }

    private function normalizeFilters(): void
    {
        $this->level = in_array(strtoupper($this->level), ['ALL', 'DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true)
            ? strtolower($this->level)
            : 'all';
        $this->module = preg_match('/\A[a-z0-9._-]{1,50}\z/i', $this->module) === 1 ? strtolower($this->module) : 'all';
        $this->limit = in_array($this->limit, [50, 100, 200], true) ? $this->limit : 100;
        $this->search = mb_substr(trim($this->search), 0, 100);
    }
}
