<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use App\Services\CustomerSlaService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
final class CustomerSlaDashboard extends Component
{
    #[Url(as: 'type', history: true)]
    public string $subjectType = 'all';

    #[Url(as: 'status', history: true)]
    public string $slaStatus = 'all';

    #[Url(as: 'q', history: true)]
    public string $search = '';

    public function mount(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        Gate::forUser($actor)->authorize('viewAny', Company::class);
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     type_label: string,
     *     type_code: string,
     *     owner_name: string,
     *     status: 'on_track'|'warning'|'breached',
     *     status_label: string,
     *     status_color: string,
     *     hours_since_interaction: int,
     *     target_hours: int,
     *     due_at: string,
     *     last_interaction_at: string,
     *     url: string
     * }>
     */
    #[Computed]
    public function slaRecords(): array
    {
        /** @var CustomerSlaService $slaService */
        $slaService = app(CustomerSlaService::class);
        /** @var DataScopeService $dataScope */
        $dataScope = app(DataScopeService::class);
        /** @var User $actor */
        $actor = Auth::user();

        $records = [];

        // 1. Leads
        if ($this->subjectType === 'all' || $this->subjectType === 'lead') {
            $leadQuery = Lead::query()
                ->whereNull('converted_at')
                ->when($this->search !== '', fn ($q) => $q->where('full_name', 'like', "%{$this->search}%"));
            $dataScope->apply($leadQuery, $actor, 'owner_id', 'department_id');
            $leads = $leadQuery->get();

            foreach ($leads as $lead) {
                $info = $slaService->getSlaInfo($lead);
                $records[] = [
                    'id' => $lead->id,
                    'name' => $lead->full_name,
                    'type_label' => 'Lead (Tiềm năng)',
                    'type_code' => 'lead',
                    'owner_name' => $lead->owner?->name ?: 'Hệ thống',
                    'status' => $info['status'],
                    'status_label' => $info['label'],
                    'status_color' => $info['color'],
                    'hours_since_interaction' => $info['hours_since_interaction'],
                    'target_hours' => $info['target_hours'],
                    'due_at' => $info['due_at']->format('H:i d/m/Y'),
                    'last_interaction_at' => $info['last_interaction_at']->format('H:i d/m/Y'),
                    'url' => route('leads.show', $lead->id),
                ];
            }
        }

        // 2. Opportunities
        if ($this->subjectType === 'all' || $this->subjectType === 'opportunity') {
            $oppQuery = Opportunity::query()
                ->open()
                ->when($this->search !== '', fn ($q) => $q->where('title', 'like', "%{$this->search}%"));
            $dataScope->apply($oppQuery, $actor, 'owner_id', 'department_id');
            $opps = $oppQuery->get();

            foreach ($opps as $opp) {
                $info = $slaService->getSlaInfo($opp);
                $records[] = [
                    'id' => $opp->id,
                    'name' => $opp->title,
                    'type_label' => 'Cơ hội bán hàng',
                    'type_code' => 'opportunity',
                    'owner_name' => $opp->owner?->name ?: 'Hệ thống',
                    'status' => $info['status'],
                    'status_label' => $info['label'],
                    'status_color' => $info['color'],
                    'hours_since_interaction' => $info['hours_since_interaction'],
                    'target_hours' => $info['target_hours'],
                    'due_at' => $info['due_at']->format('H:i d/m/Y'),
                    'last_interaction_at' => $info['last_interaction_at']->format('H:i d/m/Y'),
                    'url' => route('opportunities.show', $opp->id),
                ];
            }
        }

        // 3. Companies
        if ($this->subjectType === 'all' || $this->subjectType === 'company') {
            $companyQuery = Company::query()
                ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
            $dataScope->apply($companyQuery, $actor, 'owner_id', 'department_id');
            $companies = $companyQuery->get();

            foreach ($companies as $c) {
                $info = $slaService->getSlaInfo($c);
                $records[] = [
                    'id' => $c->id,
                    'name' => $c->name,
                    'type_label' => 'Doanh nghiệp',
                    'type_code' => 'company',
                    'owner_name' => $c->owner?->name ?: 'Hệ thống',
                    'status' => $info['status'],
                    'status_label' => $info['label'],
                    'status_color' => $info['color'],
                    'hours_since_interaction' => $info['hours_since_interaction'],
                    'target_hours' => $info['target_hours'],
                    'due_at' => $info['due_at']->format('H:i d/m/Y'),
                    'last_interaction_at' => $info['last_interaction_at']->format('H:i d/m/Y'),
                    'url' => route('companies.show', $c->id),
                ];
            }
        }

        // 4. Filter by SLA status if not all
        if ($this->slaStatus !== 'all') {
            $records = array_values(array_filter($records, fn ($r) => $r['status'] === $this->slaStatus));
        }

        // Sort by highest risk first (breached -> warning -> on_track)
        usort($records, function ($a, $b) {
            $order = ['breached' => 1, 'warning' => 2, 'on_track' => 3];

            return ($order[$a['status']] <=> $order[$b['status']])
                ?: ($b['hours_since_interaction'] <=> $a['hours_since_interaction']);
        });

        return $records;
    }

    public function render(): View
    {
        $all = $this->slaRecords();
        $breachedCount = count(array_filter($all, fn ($r) => $r['status'] === 'breached'));
        $warningCount = count(array_filter($all, fn ($r) => $r['status'] === 'warning'));
        $onTrackCount = count(array_filter($all, fn ($r) => $r['status'] === 'on_track'));

        return view('livewire.customers.customer-sla-dashboard', [
            'records' => $all,
            'breachedCount' => $breachedCount,
            'warningCount' => $warningCount,
            'onTrackCount' => $onTrackCount,
        ]);
    }
}
