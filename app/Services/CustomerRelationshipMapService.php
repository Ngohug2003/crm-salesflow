<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadStatus;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Gate;

final class CustomerRelationshipMapService
{
    public function __construct(private readonly CustomerSlaService $slaService) {}

    /**
     * Build full relationship map tree for a given Company.
     *
     * @return array<string, mixed>
     */
    public function buildCompanyMap(User $actor, int $companyId): array
    {
        /** @var Company $company */
        $company = Company::query()
            ->with(['owner', 'department', 'contacts.owner', 'opportunities.stage', 'opportunities.contact', 'opportunities.owner'])
            ->findOrFail($companyId);

        Gate::forUser($actor)->authorize('view', $company);

        // 1. Contacts
        /** @var EloquentCollection<int, Contact> $contacts */
        $contacts = Contact::query()
            ->where('company_id', $companyId)
            ->with(['owner'])
            ->orderByDesc('is_primary')
            ->orderBy('full_name')
            ->get();

        $contactIds = $contacts->pluck('id')->all();

        // 2. Opportunities (Direct to company or via contacts)
        /** @var EloquentCollection<int, Opportunity> $opportunities */
        $opportunities = Opportunity::query()
            ->where(static function ($q) use ($companyId, $contactIds): void {
                $q->where('company_id', $companyId);
                if ($contactIds !== []) {
                    $q->orWhereIn('contact_id', $contactIds);
                }
            })
            ->with(['stage', 'pipeline', 'contact', 'owner'])
            ->orderByDesc('created_at')
            ->get();

        // 3. Converted Lead Origins (Leads linked via opportunity lead_id or email/phone match)
        $linkedLeadIds = $opportunities->pluck('lead_id')->filter()->unique()->all();

        $emails = array_filter(array_merge(
            [$company->email],
            $contacts->pluck('email')->filter()->all(),
        ));

        $phones = array_filter(array_merge(
            [$company->phone],
            $contacts->pluck('phone')->filter()->all(),
        ));

        /** @var EloquentCollection<int, Lead> $convertedLeads */
        $convertedLeads = Lead::query()
            ->where(static function ($q) use ($linkedLeadIds, $emails, $phones, $company): void {
                if ($linkedLeadIds !== []) {
                    $q->whereIn('id', $linkedLeadIds);
                }
                if ($emails !== []) {
                    $q->orWhereIn('email', $emails);
                }
                if ($phones !== []) {
                    $q->orWhereIn('phone', $phones);
                }
                if (! empty($company->name)) {
                    $q->orWhere('company_name', $company->name);
                }
            })
            ->where('status', LeadStatus::Converted)
            ->with(['source', 'owner'])
            ->orderByDesc('converted_at')
            ->get();

        // 4. Recent Activities (Company and Contacts)
        /** @var EloquentCollection<int, Activity> $recentActivities */
        $recentActivities = Activity::query()
            ->where(static function ($q) use ($companyId, $contactIds): void {
                $q->where(static function ($sub) use ($companyId): void {
                    $sub->where('subject_type', Company::class)
                        ->where('subject_id', $companyId);
                });
                if ($contactIds !== []) {
                    $q->orWhere(static function ($sub) use ($contactIds): void {
                        $sub->where('subject_type', Contact::class)
                            ->whereIn('subject_id', $contactIds);
                    });
                }
            })
            ->with(['user'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        // Calculate summary metrics
        $wonOpps = $opportunities->filter(static fn (Opportunity $opp): bool => (bool) $opp->stage?->is_won);
        $openOpps = $opportunities->filter(static fn (Opportunity $opp): bool => ! (bool) $opp->stage?->is_won && ! (bool) $opp->stage?->is_lost);

        return [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'tax_code' => $company->tax_code,
                'industry' => $company->industry,
                'phone' => $company->phone,
                'email' => $company->email,
                'owner_name' => $company->owner !== null ? $company->owner->name : 'Chưa phân công',
                'department_name' => $company->department !== null ? $company->department->name : '—',
                'created_at' => $company->created_at?->format('d/m/Y'),
                'sla_info' => $this->slaService->getSlaInfo($company),
            ],
            'contacts' => $contacts->map(static fn (Contact $c): array => [
                'id' => $c->id,
                'full_name' => $c->full_name,
                'job_title' => $c->job_title,
                'email' => $c->email,
                'phone' => $c->phone,
                'is_primary' => (bool) $c->is_primary,
                'owner_name' => $c->owner !== null ? $c->owner->name : 'Chưa phân công',
            ])->all(),
            'opportunities' => array_map(static fn (Opportunity $opp): array => [
                'id' => $opp->id,
                'title' => $opp->title,
                'amount' => (float) $opp->amount,
                'stage_name' => $opp->stage !== null ? $opp->stage->name : '—',
                'is_won' => (bool) ($opp->stage !== null && $opp->stage->is_won),
                'is_lost' => (bool) ($opp->stage !== null && $opp->stage->is_lost),
                'contact_id' => $opp->contact_id,
                'contact_name' => $opp->contact !== null ? $opp->contact->full_name : null,
                'expected_close_date' => $opp->expected_close_date !== null ? (string) $opp->expected_close_date : null,
                'owner_name' => $opp->owner !== null ? $opp->owner->name : 'Chưa phân công',
            ], $opportunities->all()),
            'converted_leads' => $convertedLeads->map(static fn (Lead $lead): array => [
                'id' => $lead->id,
                'full_name' => $lead->full_name,
                'score' => $lead->score,
                'score_level_label' => $lead->score_level_label,
                'score_badge_color' => $lead->score_badge_color,
                'converted_at' => $lead->converted_at?->format('d/m/Y H:i'),
                'source_name' => $lead->source !== null ? $lead->source->name : 'Chưa có nguồn',
                'owner_name' => $lead->owner !== null ? $lead->owner->name : 'Chưa phân công',
            ])->all(),
            'recent_activities' => $recentActivities->map(static fn (Activity $act): array => [
                'id' => $act->id,
                'subject_type' => class_basename($act->subject_type),
                'description' => $act->description ?: $act->title,
                'created_at' => $act->created_at?->format('d/m/Y H:i'),
                'causer_name' => $act->user !== null ? $act->user->name : 'Hệ thống',
            ])->all(),
            'summary' => [
                'total_contacts' => $contacts->count(),
                'total_opportunities' => $opportunities->count(),
                'total_converted_leads' => $convertedLeads->count(),
                'won_opportunities' => $wonOpps->count(),
                'won_revenue' => (float) $wonOpps->sum(static fn (Opportunity $opp): float => (float) $opp->amount),
                'open_pipeline' => (float) $openOpps->sum(static fn (Opportunity $opp): float => (float) $opp->amount),
            ],
        ];
    }
}
