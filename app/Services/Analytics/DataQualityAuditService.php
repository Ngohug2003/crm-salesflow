<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Data\ReportFilterData;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use App\Services\CustomerMergeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

final readonly class DataQualityAuditService
{
    public function __construct(
        private DataScopeService $dataScope,
        private CustomerMergeService $mergeService,
    ) {}

    /**
     * Get overall Data Quality Metrics & Health Score.
     *
     * @return array{
     *     health_score: int,
     *     total_records: int,
     *     incomplete_count: int,
     *     duplicate_count: int,
     *     stale_count: int,
     *     orphan_count: int
     * }
     */
    public function getOverviewMetrics(User $actor, ReportFilterData $filters): array
    {
        $incomplete = count($this->getIncompleteRecords($actor, $filters));
        $duplicates = count($this->getDuplicateCandidates($actor));
        $stale = count($this->getStaleRecords($actor, $filters));
        $orphan = count($this->getOrphanRecords($actor, $filters));

        $totalRecords = $this->getTotalRecordsCount($actor, $filters);
        $issueCount = $incomplete + $duplicates + $stale + $orphan;

        $healthScore = 100;
        if ($totalRecords > 0) {
            $penalty = (int) round(($issueCount / max(1, $totalRecords)) * 100);
            $healthScore = max(0, 100 - $penalty);
        }

        return [
            'health_score' => $healthScore,
            'total_records' => $totalRecords,
            'incomplete_count' => $incomplete,
            'duplicate_count' => $duplicates,
            'stale_count' => $stale,
            'orphan_count' => $orphan,
        ];
    }

    /**
     * Get incomplete records (missing essential contact info / tax code / industry).
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     type_label: string,
     *     owner_name: string,
     *     missing_field: string,
     *     url: string
     * }>
     */
    public function getIncompleteRecords(User $actor, ReportFilterData $filters): array
    {
        $items = [];

        // 1. Leads missing contact info (both phone and email null/empty)
        $leadQuery = Lead::query()
            ->whereNull('converted_at')
            ->where(function ($q): void {
                $q->whereNull('phone')->orWhere('phone', '')
                    ->where(function ($q2): void {
                        $q2->whereNull('email')->orWhere('email', '');
                    });
            });
        $this->applyFilters($leadQuery, $actor, $filters);

        /** @var EloquentCollection<int, Lead> $leads */
        $leads = $leadQuery->with('owner')->latest('created_at')->take(50)->get();
        foreach ($leads as $l) {
            $items[] = [
                'id' => $l->id,
                'name' => $l->full_name,
                'type_label' => 'Lead (Tiềm năng)',
                'owner_name' => $l->owner?->name ?: 'Hệ thống',
                'missing_field' => 'Thiếu cả Số điện thoại & Email',
                'url' => route('leads.show', $l->id),
            ];
        }

        // 2. Companies missing tax code or industry
        $companyQuery = Company::query()
            ->where(function ($q): void {
                $q->whereNull('tax_code')->orWhere('tax_code', '')
                    ->orWhereNull('industry')->orWhere('industry', '');
            });
        $this->applyFilters($companyQuery, $actor, $filters);

        /** @var EloquentCollection<int, Company> $companies */
        $companies = $companyQuery->with('owner')->latest('created_at')->take(50)->get();
        foreach ($companies as $c) {
            $missing = [];
            if ($c->tax_code === null || trim((string) $c->tax_code) === '') {
                $missing[] = 'Mã số thuế';
            }
            if ($c->industry === null || trim((string) $c->industry) === '') {
                $missing[] = 'Ngành nghề';
            }
            $items[] = [
                'id' => $c->id,
                'name' => $c->name,
                'type_label' => 'Doanh nghiệp',
                'owner_name' => $c->owner?->name ?: 'Hệ thống',
                'missing_field' => 'Thiếu '.implode(' & ', $missing),
                'url' => route('companies.show', $c->id),
            ];
        }

        // 3. Contacts missing phone and email
        $contactQuery = Contact::query()
            ->where(function ($q): void {
                $q->whereNull('phone')->orWhere('phone', '')
                    ->where(function ($q2): void {
                        $q2->whereNull('email')->orWhere('email', '');
                    });
            });
        $this->applyFilters($contactQuery, $actor, $filters);

        /** @var EloquentCollection<int, Contact> $contacts */
        $contacts = $contactQuery->with('owner')->latest('created_at')->take(50)->get();
        foreach ($contacts as $cnt) {
            $items[] = [
                'id' => $cnt->id,
                'name' => $cnt->full_name,
                'type_label' => 'Người liên hệ',
                'owner_name' => $cnt->owner?->name ?: 'Hệ thống',
                'missing_field' => 'Thiếu thông tin liên lạc SĐT/Email',
                'url' => route('contacts.show', $cnt->id),
            ];
        }

        return $items;
    }

    /**
     * Get duplicate candidate pairs across Company & Contact.
     *
     * @return array<int, array{
     *     type_label: string,
     *     target_name: string,
     *     duplicate_name: string,
     *     matched_reason: string,
     *     url: string
     * }>
     */
    public function getDuplicateCandidates(User $actor): array
    {
        $candidates = [];

        // Check Company duplicates
        $companyDupes = $this->mergeService->scanCompanyDuplicates($actor);
        foreach (array_slice($companyDupes, 0, 30) as $group) {
            foreach ($group['duplicates'] as $dupe) {
                $candidates[] = [
                    'type_label' => 'Doanh nghiệp nghi trùng',
                    'target_name' => $group['master']->name,
                    'duplicate_name' => $dupe->name,
                    'matched_reason' => $group['reason'],
                    'url' => route('customers.merge'),
                ];
            }
        }

        // Check Contact duplicates
        $contactDupes = $this->mergeService->scanContactDuplicates($actor);
        foreach (array_slice($contactDupes, 0, 30) as $group) {
            foreach ($group['duplicates'] as $dupe) {
                $candidates[] = [
                    'type_label' => 'Người liên hệ nghi trùng',
                    'target_name' => $group['master']->full_name,
                    'duplicate_name' => $dupe->full_name,
                    'matched_reason' => $group['reason'],
                    'url' => route('customers.merge'),
                ];
            }
        }

        return $candidates;
    }

    /**
     * Get stale & unassigned records (Unassigned Lead or no activity > 30 days).
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     type_label: string,
     *     owner_name: string,
     *     stale_reason: string,
     *     url: string
     * }>
     */
    public function getStaleRecords(User $actor, ReportFilterData $filters): array
    {
        $items = [];
        $staleThreshold = now()->subDays(30);

        // 1. Unassigned Leads
        $unassignedQuery = Lead::query()->whereNull('converted_at')->whereNull('owner_id');
        $this->applyFilters($unassignedQuery, $actor, $filters);
        /** @var EloquentCollection<int, Lead> $unassignedLeads */
        $unassignedLeads = $unassignedQuery->latest('created_at')->take(50)->get();
        foreach ($unassignedLeads as $l) {
            $items[] = [
                'id' => $l->id,
                'name' => $l->full_name,
                'type_label' => 'Lead (Tiềm năng)',
                'owner_name' => 'Chưa phân công',
                'stale_reason' => 'Chưa được phân công người phụ trách',
                'url' => route('leads.show', $l->id),
            ];
        }

        // 2. Open Opportunities with no updates > 30 days
        $staleOppQuery = Opportunity::query()->open()->where('updated_at', '<', $staleThreshold);
        $this->applyFilters($staleOppQuery, $actor, $filters);
        /** @var EloquentCollection<int, Opportunity> $staleOpps */
        $staleOpps = $staleOppQuery->with('owner')->latest('updated_at')->take(50)->get();
        foreach ($staleOpps as $opp) {
            $items[] = [
                'id' => $opp->id,
                'name' => $opp->title,
                'type_label' => 'Cơ hội bán hàng',
                'owner_name' => $opp->owner?->name ?: 'Hệ thống',
                'stale_reason' => 'Không có tương tác/cập nhật > 30 ngày',
                'url' => route('opportunities.show', $opp->id),
            ];
        }

        return $items;
    }

    /**
     * Get orphan records (Contact without Company, Opportunity without Customer).
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     type_label: string,
     *     owner_name: string,
     *     orphan_reason: string,
     *     url: string
     * }>
     */
    public function getOrphanRecords(User $actor, ReportFilterData $filters): array
    {
        $items = [];

        // 1. Contact without Company
        $orphanContactQuery = Contact::query()->whereNull('company_id');
        $this->applyFilters($orphanContactQuery, $actor, $filters);
        /** @var EloquentCollection<int, Contact> $orphanContacts */
        $orphanContacts = $orphanContactQuery->with('owner')->latest('created_at')->take(50)->get();
        foreach ($orphanContacts as $cnt) {
            $items[] = [
                'id' => $cnt->id,
                'name' => $cnt->full_name,
                'type_label' => 'Người liên hệ mồ côi',
                'owner_name' => $cnt->owner?->name ?: 'Hệ thống',
                'orphan_reason' => 'Chưa thuộc Doanh nghiệp nào',
                'url' => route('contacts.show', $cnt->id),
            ];
        }

        // 2. Opportunity without Company and Contact
        $orphanOppQuery = Opportunity::query()->open()->whereNull('company_id')->whereNull('contact_id');
        $this->applyFilters($orphanOppQuery, $actor, $filters);
        /** @var EloquentCollection<int, Opportunity> $orphanOpps */
        $orphanOpps = $orphanOppQuery->with('owner')->latest('created_at')->take(50)->get();
        foreach ($orphanOpps as $opp) {
            $items[] = [
                'id' => $opp->id,
                'name' => $opp->title,
                'type_label' => 'Cơ hội bán hàng mồ côi',
                'owner_name' => $opp->owner?->name ?: 'Hệ thống',
                'orphan_reason' => 'Chưa liên kết với Doanh nghiệp hay Người liên hệ nào',
                'url' => route('opportunities.show', $opp->id),
            ];
        }

        return $items;
    }

    private function getTotalRecordsCount(User $actor, ReportFilterData $filters): int
    {
        $leadQuery = Lead::query();
        $this->applyFilters($leadQuery, $actor, $filters);

        $companyQuery = Company::query();
        $this->applyFilters($companyQuery, $actor, $filters);

        $contactQuery = Contact::query();
        $this->applyFilters($contactQuery, $actor, $filters);

        $oppQuery = Opportunity::query();
        $this->applyFilters($oppQuery, $actor, $filters);

        return $leadQuery->count() + $companyQuery->count() + $contactQuery->count() + $oppQuery->count();
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     */
    private function applyFilters(Builder $query, User $actor, ReportFilterData $filters): void
    {
        $this->dataScope->apply($query, $actor, 'owner_id', 'department_id');

        if ($filters->departmentId !== null) {
            $query->where('department_id', $filters->departmentId);
        }

        if ($filters->userId !== null) {
            $query->where('owner_id', $filters->userId);
        }
    }
}
