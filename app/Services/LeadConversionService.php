<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\LeadConversionData;
use App\Enums\LeadStatus;
use App\Exceptions\LeadConversionException;
use App\Models\Lead;
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
                    'company_id' => $data->companyId,
                    'create_contact' => $data->createContact,
                    'contact_id' => $data->contactId,
                    'create_opportunity' => $data->createOpportunity,
                ],
            );

            return $savedLead;
        });
    }
}
