<?php

namespace App\Providers;

use App\Models\Activity as SalesActivity;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Services\Analytics\MetricsCacheVersionService;
use App\Support\RequestContext;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Event listeners in app/Listeners are discovered automatically by Laravel.
        Event::listen(ConnectionEstablished::class, static function (ConnectionEstablished $event): void {
            if ($event->connection->getDriverName() === 'pgsql') {
                $event->connection->statement("SET TIME ZONE 'Asia/Ho_Chi_Minh'");
            }
        });

        Activity::creating(static function (Activity $activity): void {
            $activity->request_id = app(RequestContext::class)->id();
        });

        foreach ([Lead::class, Opportunity::class, OpportunityStageHistory::class, Pipeline::class, PipelineStage::class, Task::class, SalesActivity::class] as $model) {
            $invalidateMetrics = static fn (): int => app(MetricsCacheVersionService::class)->bump();
            $model::saved($invalidateMetrics);
            $model::deleted($invalidateMetrics);
        }
    }
}
