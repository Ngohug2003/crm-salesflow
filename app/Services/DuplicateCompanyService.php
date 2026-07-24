<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Database\Eloquent\Builder;

final readonly class DuplicateCompanyService
{
    public function __construct(private DataScopeService $dataScope) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<array{id: int, name: string, tax_code: ?string, email: ?string, phone: ?string, owner: string, matched_fields: list<string>}>
     */
    public function candidates(User $actor, array $attributes, ?int $excludeCompanyId = null): array
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $taxCode = trim((string) ($attributes['tax_code'] ?? ''));
        $email = trim((string) ($attributes['email'] ?? ''));
        $phone = trim((string) ($attributes['phone'] ?? ''));

        if ($name === '' && $taxCode === '' && $email === '' && $phone === '') {
            return [];
        }

        $query = $this->dataScope->apply(Company::query(), $actor, 'owner_id', 'department_id');

        if ($excludeCompanyId !== null) {
            $query->where('companies.id', '!=', $excludeCompanyId);
        }

        $query->where(function (Builder $q) use ($name, $taxCode, $email, $phone): void {
            if ($taxCode !== '') {
                $q->orWhere('tax_code', $taxCode);
            }
            if ($name !== '') {
                $q->orWhereLike('name', $name, caseSensitive: false);
            }
            if ($email !== '') {
                $q->orWhere('email', $email);
            }
            if ($phone !== '') {
                $q->orWhere('phone', $phone);
            }
        });

        $companies = $query->with('owner')->get();
        $candidates = [];

        foreach ($companies as $comp) {
            $matched = [];
            if ($taxCode !== '' && $comp->tax_code === $taxCode) {
                $matched[] = 'Mã số thuế';
            }
            if ($name !== '' && mb_strtolower($comp->name) === mb_strtolower($name)) {
                $matched[] = 'Tên doanh nghiệp';
            }
            if ($email !== '' && mb_strtolower((string) $comp->email) === mb_strtolower($email)) {
                $matched[] = 'Email';
            }
            if ($phone !== '' && $comp->phone === $phone) {
                $matched[] = 'Số điện thoại';
            }

            if ($matched !== []) {
                $candidates[] = [
                    'id' => $comp->getKey(),
                    'name' => $comp->name,
                    'tax_code' => $comp->tax_code,
                    'email' => $comp->email,
                    'phone' => $comp->phone,
                    'owner' => $comp->owner ? $comp->owner->name : 'Chưa phân công',
                    'matched_fields' => $matched,
                ];
            }
        }

        return $candidates;
    }

    /** @param array<string, mixed> $attributes */
    public function signature(array $attributes): string
    {
        $taxCode = trim((string) ($attributes['tax_code'] ?? ''));
        $name = mb_strtolower(trim((string) ($attributes['name'] ?? '')));
        $email = mb_strtolower(trim((string) ($attributes['email'] ?? '')));
        $phone = trim((string) ($attributes['phone'] ?? ''));

        return md5("company|{$taxCode}|{$name}|{$email}|{$phone}");
    }
}
