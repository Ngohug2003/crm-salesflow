<?php

namespace App\Providers;

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
    }
}
