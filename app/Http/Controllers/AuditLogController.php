<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

final class AuditLogController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Activity::class);

        return view('audit-logs.index');
    }
}
