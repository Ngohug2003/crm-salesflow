<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

final readonly class ReportDrillDownService
{
    public function __construct(
        private DataScopeService $dataScope,
    ) {}

    /**
     * Fetch drill-down records matching the type and scope for actor.
     *
     * @return array{
     *     title: string,
     *     type: string,
     *     columns: array<int, string>,
     *     rows: array<int, array<string, mixed>>
     * }
     */
    public function getDrillDownData(User $actor, string $type, ?int $departmentId = null, ?int $ownerId = null): array
    {
        return match ($type) {
            'won_opportunities' => $this->getWonOpportunities($actor, $departmentId, $ownerId),
            'open_opportunities' => $this->getOpenOpportunities($actor, $departmentId, $ownerId),
            'lost_opportunities' => $this->getLostOpportunities($actor, $departmentId, $ownerId),
            'new_leads' => $this->getNewLeads($actor, $departmentId, $ownerId),
            'overdue_tasks' => $this->getOverdueTasks($actor, $departmentId, $ownerId),
            default => [
                'title' => 'Chi tiết chỉ số',
                'type' => $type,
                'columns' => ['Tên', 'Mô tả'],
                'rows' => [],
            ],
        };
    }

    /**
     * @return array{title: string, type: string, columns: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    private function getWonOpportunities(User $actor, ?int $departmentId, ?int $ownerId): array
    {
        $query = Opportunity::query()
            ->with(['company', 'contact', 'owner', 'stage'])
            ->won();

        $query = $this->dataScope->apply($query, $actor, 'owner_id', 'department_id');

        if ($departmentId !== null) {
            $query->where('department_id', $departmentId);
        }
        if ($ownerId !== null) {
            $query->where('owner_id', $ownerId);
        }

        /** @var EloquentCollection<int, Opportunity> $items */
        $items = $query->latest('updated_at')->take(50)->get();

        $rows = [];
        foreach ($items as $opp) {
            $rows[] = [
                'id' => $opp->id,
                'name' => $opp->title,
                'customer' => $opp->company?->name ?: ($opp->contact?->full_name ?: '—'),
                'stage' => $opp->stage?->name ?: 'Thành công',
                'owner' => $opp->owner?->name ?: 'Hệ thống',
                'amount' => number_format((float) $opp->amount, 0, ',', '.').' ₫',
                'url' => route('opportunities.show', $opp->id),
            ];
        }

        return [
            'title' => 'Danh sách Cơ hội bán hàng Chốt Thành công (Won)',
            'type' => 'won_opportunities',
            'columns' => ['Tên cơ hội', 'Khách hàng', 'Giai đoạn', 'Người phụ trách', 'Doanh thu'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{title: string, type: string, columns: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    private function getOpenOpportunities(User $actor, ?int $departmentId, ?int $ownerId): array
    {
        $query = Opportunity::query()
            ->with(['company', 'contact', 'owner', 'stage'])
            ->open();

        $query = $this->dataScope->apply($query, $actor, 'owner_id', 'department_id');

        if ($departmentId !== null) {
            $query->where('department_id', $departmentId);
        }
        if ($ownerId !== null) {
            $query->where('owner_id', $ownerId);
        }

        /** @var EloquentCollection<int, Opportunity> $items */
        $items = $query->latest('updated_at')->take(50)->get();

        $rows = [];
        foreach ($items as $opp) {
            $rows[] = [
                'id' => $opp->id,
                'name' => $opp->title,
                'customer' => $opp->company?->name ?: ($opp->contact?->full_name ?: '—'),
                'stage' => $opp->stage?->name ?: 'Đang xử lý',
                'owner' => $opp->owner?->name ?: 'Hệ thống',
                'amount' => number_format((float) $opp->amount, 0, ',', '.').' ₫',
                'url' => route('opportunities.show', $opp->id),
            ];
        }

        return [
            'title' => 'Danh sách Cơ hội bán hàng Đang mở (Open Pipeline)',
            'type' => 'open_opportunities',
            'columns' => ['Tên cơ hội', 'Khách hàng', 'Giai đoạn', 'Người phụ trách', 'Doanh thu dự kiến'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{title: string, type: string, columns: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    private function getLostOpportunities(User $actor, ?int $departmentId, ?int $ownerId): array
    {
        $query = Opportunity::query()
            ->with(['company', 'contact', 'owner', 'stage'])
            ->lost();

        $query = $this->dataScope->apply($query, $actor, 'owner_id', 'department_id');

        if ($departmentId !== null) {
            $query->where('department_id', $departmentId);
        }
        if ($ownerId !== null) {
            $query->where('owner_id', $ownerId);
        }

        /** @var EloquentCollection<int, Opportunity> $items */
        $items = $query->latest('updated_at')->take(50)->get();

        $rows = [];
        foreach ($items as $opp) {
            $rows[] = [
                'id' => $opp->id,
                'name' => $opp->title,
                'customer' => $opp->company?->name ?: ($opp->contact?->full_name ?: '—'),
                'stage' => $opp->stage?->name ?: 'Thất bại',
                'owner' => $opp->owner?->name ?: 'Hệ thống',
                'amount' => number_format((float) $opp->amount, 0, ',', '.').' ₫',
                'url' => route('opportunities.show', $opp->id),
            ];
        }

        return [
            'title' => 'Danh sách Cơ hội bán hàng Thất bại (Lost)',
            'type' => 'lost_opportunities',
            'columns' => ['Tên cơ hội', 'Khách hàng', 'Giai đoạn', 'Người phụ trách', 'Doanh thu mất'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{title: string, type: string, columns: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    private function getNewLeads(User $actor, ?int $departmentId, ?int $ownerId): array
    {
        $query = Lead::query()
            ->with('owner')
            ->whereNull('converted_at');

        $query = $this->dataScope->apply($query, $actor, 'owner_id', 'department_id');

        if ($departmentId !== null) {
            $query->where('department_id', $departmentId);
        }
        if ($ownerId !== null) {
            $query->where('owner_id', $ownerId);
        }

        /** @var EloquentCollection<int, Lead> $items */
        $items = $query->latest('created_at')->take(50)->get();

        $rows = [];
        foreach ($items as $lead) {
            $rows[] = [
                'id' => $lead->id,
                'name' => $lead->full_name,
                'customer' => $lead->email ?: ($lead->phone ?: 'Chưa có thông tin'),
                'stage' => 'Chưa chuyển đổi',
                'owner' => $lead->owner?->name ?: 'Hệ thống',
                'amount' => '—',
                'url' => route('leads.show', $lead->id),
            ];
        }

        return [
            'title' => 'Danh sách Khách hàng tiềm năng (Leads) chưa chuyển đổi',
            'type' => 'new_leads',
            'columns' => ['Họ tên Lead', 'Liên hệ', 'Trạng thái', 'Người phụ trách', 'Ghi chú'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{title: string, type: string, columns: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    private function getOverdueTasks(User $actor, ?int $departmentId, ?int $ownerId): array
    {
        $query = Task::query()
            ->with('assignee')
            ->where('status', '!=', TaskStatus::Completed)
            ->where('due_date', '<', now());

        $query = $this->dataScope->apply($query, $actor, 'assignee_id', 'department_id');

        /** @var EloquentCollection<int, Task> $items */
        $items = $query->latest('due_date')->take(50)->get();

        $rows = [];
        foreach ($items as $t) {
            $rows[] = [
                'id' => $t->id,
                'name' => $t->title,
                'customer' => $t->due_date ? $t->due_date->format('d/m/Y') : '—',
                'stage' => $t->status->label(),
                'owner' => $t->assignee?->name ?: 'Hệ thống',
                'amount' => '—',
                'url' => route('tasks.show', $t->id),
            ];
        }

        return [
            'title' => 'Danh sách Công việc (Tasks) quá hạn xử lý',
            'type' => 'overdue_tasks',
            'columns' => ['Tiêu đề công việc', 'Hạn hoàn thành', 'Trạng thái', 'Người thực hiện', 'Chi tiết'],
            'rows' => $rows,
        ];
    }
}
