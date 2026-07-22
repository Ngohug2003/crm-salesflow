<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class LeadController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Lead::class);

        return view('leads.index');
    }
}
