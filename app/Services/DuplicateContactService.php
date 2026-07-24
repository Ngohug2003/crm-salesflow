<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Database\Eloquent\Builder;

final readonly class DuplicateContactService
{
    public function __construct(private DataScopeService $dataScope) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<array{id: int, full_name: string, company: string, email: ?string, phone: ?string, owner: string, matched_fields: list<string>}>
     */
    public function candidates(User $actor, array $attributes, ?int $excludeContactId = null): array
    {
        $firstName = trim((string) ($attributes['first_name'] ?? ''));
        $lastName = trim((string) ($attributes['last_name'] ?? ''));
        $fullName = trim((string) ($attributes['full_name'] ?? "{$lastName} {$firstName}"));
        $email = trim((string) ($attributes['email'] ?? ''));
        $phone = trim((string) ($attributes['phone'] ?? ''));
        $secPhone = trim((string) ($attributes['secondary_phone'] ?? ''));
        $companyId = isset($attributes['company_id']) && $attributes['company_id'] !== '' ? (int) $attributes['company_id'] : null;

        if ($fullName === '' && $email === '' && $phone === '' && $secPhone === '') {
            return [];
        }

        $query = $this->dataScope->apply(Contact::query(), $actor, 'owner_id', 'department_id');

        if ($excludeContactId !== null) {
            $query->where('contacts.id', '!=', $excludeContactId);
        }

        $query->where(function (Builder $q) use ($fullName, $email, $phone, $secPhone, $companyId): void {
            if ($email !== '') {
                $q->orWhere('email', $email);
            }
            if ($phone !== '') {
                $q->orWhere('phone', $phone)->orWhere('secondary_phone', $phone);
            }
            if ($secPhone !== '') {
                $q->orWhere('phone', $secPhone)->orWhere('secondary_phone', $secPhone);
            }
            if ($fullName !== '' && $companyId !== null) {
                $q->orWhere(function (Builder $sub) use ($fullName, $companyId): void {
                    $sub->where('company_id', $companyId)
                        ->whereLike('full_name', $fullName, caseSensitive: false);
                });
            }
        });

        $contacts = $query->with(['owner', 'company'])->get();
        $candidates = [];

        foreach ($contacts as $cont) {
            $matched = [];
            if ($email !== '' && mb_strtolower((string) $cont->email) === mb_strtolower($email)) {
                $matched[] = 'Email';
            }
            if ($phone !== '' && ($cont->phone === $phone || $cont->secondary_phone === $phone)) {
                $matched[] = 'Số điện thoại';
            }
            if ($secPhone !== '' && ($cont->phone === $secPhone || $cont->secondary_phone === $secPhone)) {
                $matched[] = 'Số điện thoại phụ';
            }
            if ($companyId !== null && $cont->company_id === $companyId && mb_strtolower($cont->full_name) === mb_strtolower($fullName)) {
                $matched[] = 'Họ tên & Doanh nghiệp';
            }

            if ($matched !== []) {
                $candidates[] = [
                    'id' => $cont->getKey(),
                    'full_name' => $cont->full_name,
                    'company' => $cont->company ? $cont->company->name : 'Cá nhân tự do',
                    'email' => $cont->email,
                    'phone' => $cont->phone,
                    'owner' => $cont->owner ? $cont->owner->name : 'Chưa phân công',
                    'matched_fields' => array_unique($matched),
                ];
            }
        }

        return $candidates;
    }

    /** @param array<string, mixed> $attributes */
    public function signature(array $attributes): string
    {
        $email = mb_strtolower(trim((string) ($attributes['email'] ?? '')));
        $phone = trim((string) ($attributes['phone'] ?? ''));
        $secPhone = trim((string) ($attributes['secondary_phone'] ?? ''));
        $companyId = (string) ($attributes['company_id'] ?? '');

        return md5("contact|{$email}|{$phone}|{$secPhone}|{$companyId}");
    }
}
