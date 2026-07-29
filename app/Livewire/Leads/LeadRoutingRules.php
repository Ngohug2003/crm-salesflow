<?php

declare(strict_types=1);

namespace App\Livewire\Leads;

use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadRoutingExecution;
use App\Models\LeadRoutingRule;
use App\Models\LeadRoutingRuleCondition;
use App\Models\LeadSource;
use App\Services\LeadRoutingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

final class LeadRoutingRules extends Component
{
    use WithPagination;

    public string $activeTab = 'rules'; // rules, executions, unassigned

    // Modal state for Rule
    public bool $showRuleModal = false;

    public ?int $editingRuleId = null;

    public string $ruleName = '';

    public string $strategy = 'round_robin';

    public int $priority = 1;

    public bool $isActive = true;

    public ?int $departmentId = null;

    // Rule Conditions
    public ?int $leadSourceId = null;

    public ?string $minEstimatedValue = null;

    public function mount(): void
    {
        $user = Auth::user();
        if ($user === null || ! $user->hasAnyRole(['admin', 'super-admin', 'sales-manager', 'IT Admin', 'Super Admin'])) {
            abort(403, 'Bạn không có quyền quản lý quy tắc phân bổ Lead.');
        }
    }

    public function openCreateRule(): void
    {
        $this->resetRuleForm();
        $this->showRuleModal = true;
    }

    public function editRule(int $ruleId): void
    {
        $rule = LeadRoutingRule::query()->with('conditions')->findOrFail($ruleId);
        $this->editingRuleId = $rule->id;
        $this->ruleName = $rule->name;
        $this->strategy = $rule->strategy;
        $this->priority = $rule->priority;
        $this->isActive = $rule->is_active;
        $this->departmentId = $rule->department_id;

        $firstCondition = $rule->conditions->first();
        if ($firstCondition !== null) {
            $this->leadSourceId = $firstCondition->lead_source_id;
            $this->minEstimatedValue = $firstCondition->min_estimated_value;
        } else {
            $this->leadSourceId = null;
            $this->minEstimatedValue = null;
        }

        $this->showRuleModal = true;
    }

    public function saveRule(): void
    {
        $this->validate([
            'ruleName' => ['required', 'string', 'max:255'],
            'strategy' => ['required', 'string', 'in:round_robin,value_threshold'],
            'priority' => ['required', 'integer', 'min:1'],
            'departmentId' => ['nullable', 'integer', 'exists:departments,id'],
            'leadSourceId' => ['nullable', 'integer', 'exists:lead_sources,id'],
            'minEstimatedValue' => ['nullable', 'numeric', 'min:0'],
        ]);

        $rule = LeadRoutingRule::query()->updateOrCreate(
            ['id' => $this->editingRuleId],
            [
                'name' => $this->ruleName,
                'strategy' => $this->strategy,
                'priority' => $this->priority,
                'is_active' => $this->isActive,
                'department_id' => $this->departmentId,
            ]
        );

        $rule->conditions()->delete();

        if ($this->leadSourceId !== null || ($this->minEstimatedValue !== null && (float) $this->minEstimatedValue > 0)) {
            LeadRoutingRuleCondition::query()->create([
                'rule_id' => $rule->id,
                'lead_source_id' => $this->leadSourceId,
                'min_estimated_value' => $this->minEstimatedValue,
            ]);
        }

        $this->showRuleModal = false;
        $this->resetRuleForm();
        session()->flash('success', 'Đã lưu quy tắc phân bổ thành công.');
    }

    public function toggleRuleStatus(int $ruleId): void
    {
        $rule = LeadRoutingRule::query()->findOrFail($ruleId);
        $rule->update(['is_active' => ! $rule->is_active]);
        session()->flash('success', "Đã thay đổi trạng thái quy tắc [{$rule->name}].");
    }

    public function deleteRule(int $ruleId): void
    {
        LeadRoutingRule::query()->where('id', $ruleId)->delete();
        session()->flash('success', 'Đã xóa quy tắc phân bổ thành công.');
    }

    public function routeSingleLead(int $leadId, LeadRoutingService $routingService): void
    {
        $lead = Lead::query()->findOrFail($leadId);
        $execution = $routingService->routeLead($lead, Auth::user());

        if ($execution->assigned_user_id !== null) {
            session()->flash('success', "Đã phân bổ Lead [{$lead->full_name}] thành công cho {$execution->assignedUser?->name}.");
        } else {
            session()->flash('warning', "Không thể phân bổ Lead [{$lead->full_name}]: {$execution->reason}.");
        }
    }

    public function render(): View
    {
        $rules = LeadRoutingRule::query()
            ->with(['department', 'conditions.leadSource', 'cursor.lastAssignedUser'])
            ->orderBy('priority', 'asc')
            ->get();

        $executions = LeadRoutingExecution::query()
            ->with([
                'lead.provinceUnit',
                'lead.source',
                'rule.conditions.leadSource',
                'assignedUser.staffProfile',
                'executor',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'executionsPage');

        $unassignedLeads = Lead::query()
            ->whereNull('owner_id')
            ->orWhere('is_sla_overdue', true)
            ->with(['source', 'provinceUnit'])
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'unassignedPage');

        $departments = Department::query()->orderBy('name')->get();
        $leadSources = LeadSource::query()->orderBy('name')->get();

        return view('livewire.leads.lead-routing-rules', [
            'rules' => $rules,
            'executions' => $executions,
            'unassignedLeads' => $unassignedLeads,
            'departments' => $departments,
            'leadSources' => $leadSources,
        ])->layout('layouts.app', ['title' => 'Quy tắc Phân bổ Lead & SLA']);
    }

    private function resetRuleForm(): void
    {
        $this->editingRuleId = null;
        $this->ruleName = '';
        $this->strategy = 'round_robin';
        $this->priority = 1;
        $this->isActive = true;
        $this->departmentId = null;
        $this->leadSourceId = null;
        $this->minEstimatedValue = null;
    }
}
