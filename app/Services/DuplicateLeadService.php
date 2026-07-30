<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadStatus;
use App\Models\User;
use App\Repositories\Contracts\LeadRepository;
use App\Support\LeadContactNormalizer;

final readonly class DuplicateLeadService
{
    public function __construct(private LeadRepository $leads) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<array{id: int, full_name: string, email: ?string, phone: ?string, status: string, owner: string, department: string, trashed: bool, matched_fields: list<string>}>
     */
    public function candidates(User $actor, array $attributes, ?int $excludeLeadId = null): array
    {
        $email = LeadContactNormalizer::email($this->stringValue($attributes['email'] ?? null));
        $phones = array_values(array_filter([
            LeadContactNormalizer::phone($this->stringValue($attributes['phone'] ?? null)),
            LeadContactNormalizer::phone($this->stringValue($attributes['secondary_phone'] ?? null)),
        ]));

        $candidates = [];

        foreach ($this->leads->duplicateCandidates($actor, $email, $phones, $excludeLeadId) as $lead) {
            $matchedFields = [];

            if ($email !== null && $lead->getAttribute('email_normalized') === $email) {
                $matchedFields[] = 'Email';
            }

            if (in_array($lead->getAttribute('phone_normalized'), $phones, true)
                || in_array($lead->getAttribute('secondary_phone_normalized'), $phones, true)) {
                $matchedFields[] = 'Số điện thoại';
            }

            $status = $lead->getAttribute('status');
            $candidates[] = [
                'id' => $lead->getKey(),
                'full_name' => $lead->full_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'status' => $status instanceof LeadStatus ? $status->label() : (string) $status,
                'owner' => $lead->owner_id === null ? 'Chưa phân công' : $lead->owner->name,
                'department' => $lead->department_id === null ? 'Chưa gán' : $lead->department->name,
                'trashed' => $lead->trashed(),
                'matched_fields' => $matchedFields,
            ];
        }

        return $candidates;
    }

    /** @param array<string, mixed> $attributes */
    public function signature(array $attributes): string
    {
        return hash('sha256', json_encode([
            LeadContactNormalizer::email($this->stringValue($attributes['email'] ?? null)),
            LeadContactNormalizer::phone($this->stringValue($attributes['phone'] ?? null)),
            LeadContactNormalizer::phone($this->stringValue($attributes['secondary_phone'] ?? null)),
        ], JSON_THROW_ON_ERROR));
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
