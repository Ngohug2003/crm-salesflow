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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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
        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        RateLimiter::for('public-quote', static fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('public-catalog', static fn (Request $request): Limit => Limit::perMinute(120)->by($request->ip()));

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
