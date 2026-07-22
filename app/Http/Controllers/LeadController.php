<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Repositories\Contracts\LeadRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class LeadController extends Controller
{
    public function __construct(private readonly LeadRepository $leads) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Lead::class);

        return view('leads.index');
    }

    public function create(): View
    {
        Gate::authorize('create', Lead::class);

        return view('leads.create');
    }

    public function trash(): View
    {
        Gate::authorize('viewTrash', Lead::class);

        return view('leads.trash');
    }

    public function show(int $leadId): View
    {
        $lead = $this->leads->findVisibleOrFail($this->currentUser(), $leadId);
        Gate::authorize('view', $lead);

        return view('leads.show', ['lead' => $lead]);
    }

    public function edit(int $leadId): View
    {
        $lead = $this->leads->findVisibleOrFail($this->currentUser(), $leadId);
        Gate::authorize('update', $lead);

        return view('leads.edit', ['lead' => $lead]);
    }

    private function currentUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
