<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\LeadConversionData;
use App\Enums\LeadStatus;
use App\Exceptions\LeadConversionException;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Repositories\Contracts\LeadRepository;
use App\Repositories\Contracts\LeadWorkflowRepository;
use App\Services\Contracts\LeadConversionContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class LeadConversionService implements LeadConversionContract
{
    public function __construct(
        private LeadRepository $leads,
        private LeadWorkflowRepository $workflow,
        private SystemAuditService $audit,
    ) {}

    /**
     * @return array{eligible: bool, reason: ?string, reason_code: ?string}
     */
    public function checkEligibility(User $actor, Lead $lead): array
    {
        if (! Gate::forUser($actor)->allows('convert', $lead)) {
            return [
                'eligible' => false,
                'reason' => 'Bạn không có quyền chuyển đổi Lead này.',
                'reason_code' => 'unauthorized',
            ];
        }

        if ($lead->trashed()) {
            return [
                'eligible' => false,
                'reason' => 'Không thể chuyển đổi Lead đã bị xóa.',
                'reason_code' => 'trashed',
            ];
        }

        $currentStatus = $lead->getAttribute('status');
        $currentStatus = $currentStatus instanceof LeadStatus ? $currentStatus : LeadStatus::from((string) $currentStatus);

        if ($currentStatus === LeadStatus::Converted) {
            return [
                'eligible' => false,
                'reason' => 'Lead đã được chuyển đổi trước đó.',
                'reason_code' => 'already_converted',
            ];
        }

        $fullName = trim((string) $lead->getAttribute('full_name'));
        $email = trim((string) $lead->getAttribute('email'));
        $phone = trim((string) $lead->getAttribute('phone'));
        $secondaryPhone = trim((string) $lead->getAttribute('secondary_phone'));

        if ($fullName === '' || ($email === '' && $phone === '' && $secondaryPhone === '')) {
            return [
                'eligible' => false,
                'reason' => 'Lead phải có họ tên và thông tin liên hệ (email hoặc số điện thoại).',
                'reason_code' => 'missing_contact',
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
            'reason_code' => null,
        ];
    }

    public function convert(User $actor, int $leadId, LeadConversionData $data): Lead
    {
        return DB::transaction(function () use ($actor, $leadId, $data): Lead {
            $lead = $this->leads->findVisibleForUpdateOrFail($actor, $leadId);

            Gate::forUser($actor)->authorize('convert', $lead);

            $eligibility = $this->checkEligibility($actor, $lead);

            if (! $eligibility['eligible']) {
                throw new LeadConversionException(
                    $eligibility['reason_code'] ?? 'ineligible',
                    $eligibility['reason'] ?? 'Lead không đủ điều kiện chuyển đổi.',
                );
            }

            $currentStatus = $lead->getAttribute('status');
            $currentStatus = $currentStatus instanceof LeadStatus ? $currentStatus : LeadStatus::from((string) $currentStatus);

            // 1. Resolve or Create Company
            $companyId = $data->companyId;
            if ($companyId === null && $data->createCompany) {
                $leadCompanyName = trim((string) $lead->getAttribute('company_name'));
                $companyName = $data->companyName !== null && trim($data->companyName) !== ''
                    ? trim($data->companyName)
                    : ($leadCompanyName !== '' ? $leadCompanyName : 'Công ty từ Lead '.$lead->full_name);

                $company = Company::query()->create([
                    'name' => $companyName,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'owner_id' => $lead->owner_id,
                    'department_id' => $lead->department_id,
                    'created_by' => $actor->getKey(),
                    'updated_by' => $actor->getKey(),
                ]);
                $companyId = $company->id;
            }

            // 2. Resolve or Create Contact
            $contactId = $data->contactId;
            if ($contactId === null && $data->createContact) {
                $nameParts = explode(' ', trim((string) $lead->full_name), 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? null;

                $contact = Contact::query()->create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'full_name' => trim((string) $lead->full_name),
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'company_id' => $companyId,
                    'owner_id' => $lead->owner_id,
                    'department_id' => $lead->department_id,
                    'created_by' => $actor->getKey(),
                    'updated_by' => $actor->getKey(),
                ]);
                $contactId = $contact->id;
            }

            // 3. Resolve or Create Opportunity if requested
            $createdOpportunityId = null;
            if ($data->createOpportunity) {
                $pipelineId = $data->pipelineId;
                if ($pipelineId === null) {
                    $defaultPipeline = Pipeline::query()->where('is_default', true)->first()
                        ?? Pipeline::query()->where('is_active', true)->first();
                    $pipelineId = $defaultPipeline?->id;
                }

                if ($pipelineId !== null) {
                    $stageId = $data->stageId;
                    if ($stageId === null) {
                        $firstStage = PipelineStage::query()
                            ->where('pipeline_id', $pipelineId)
                            ->orderBy('position', 'asc')
                            ->first();
                        $stageId = $firstStage?->id;
                    }

                    if ($stageId !== null) {
                        $opportunityName = $data->opportunityName !== null && trim($data->opportunityName) !== ''
                            ? trim($data->opportunityName)
                            : "Cơ hội từ Lead {$lead->full_name}";

                        /** @var OpportunityManagementService $oppManagement */
                        $oppManagement = app(OpportunityManagementService::class);
                        $createdOpportunity = $oppManagement->create($actor, [
                            'title' => $opportunityName,
                            'amount' => $data->estimatedValue ?? 0.0,
                            'pipeline_id' => $pipelineId,
                            'stage_id' => $stageId,
                            'company_id' => $companyId,
                            'contact_id' => $contactId,
                            'lead_id' => $lead->id,
                            'owner_id' => $lead->owner_id,
                            'department_id' => $lead->department_id,
                            'notes' => $data->notes,
                        ]);
                        $createdOpportunityId = $createdOpportunity->id;
                    }
                }
            }

            $savedLead = $this->leads->update($lead, [
                'status' => LeadStatus::Converted,
                'converted_at' => now(),
                'updated_by' => $actor->getKey(),
            ]);

            $this->workflow->createStatusHistory([
                'lead_id' => $savedLead->getKey(),
                'from_status' => $currentStatus,
                'to_status' => LeadStatus::Converted,
                'changed_by' => $actor->getKey(),
                'reason' => 'Chuyển đổi Lead theo quy trình CRM',
            ]);

            $this->audit->record(
                $actor,
                $savedLead,
                'converted',
                'Chuyển đổi Lead thành công',
                ['status' => $currentStatus->value],
                [
                    'status' => LeadStatus::Converted->value,
                    'converted_at' => $savedLead->getAttribute('converted_at')?->toIso8601String(),
                ],
                [
                    'create_company' => $data->createCompany,
                    'company_id' => $companyId,
                    'create_contact' => $data->createContact,
                    'contact_id' => $contactId,
                    'create_opportunity' => $data->createOpportunity,
                    'opportunity_id' => $createdOpportunityId,
                ],
            );

            return $savedLead;
        });
    }
}
