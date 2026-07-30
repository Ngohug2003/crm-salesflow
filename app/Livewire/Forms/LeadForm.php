<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\LeadPriority;
use App\Models\Lead;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

final class LeadForm extends Form
{
    public ?int $leadId = null;

    public string $sourceId = '';

    public string $ownerId = '';

    public string $fullName = '';

    public string $email = '';

    public string $phone = '';

    public string $secondaryPhone = '';

    public string $companyName = '';

    public string $jobTitle = '';

    public string $website = '';

    public string $address = '';

    public ?string $provinceId = null;

    public ?string $wardId = null;

    public string $city = '';

    public string $province = '';

    public string $country = 'Việt Nam';

    public string $priority = 'medium';

    public string $estimatedValue = '';

    public string $notes = '';

    public bool $administrativeUnitSelectionChanged = false;

    /** @var list<int|string> */
    public array $tagIds = [];

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        return [
            'sourceId' => [
                'nullable',
                'integer',
                Rule::exists('lead_sources', 'id')->where(
                    fn (Builder $query): Builder => $query->where('is_active', true),
                ),
            ],
            'ownerId' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn (Builder $query): Builder => $query->where('is_active', true),
                ),
            ],
            'fullName' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'string', 'lowercase', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'secondaryPhone' => ['nullable', 'string', 'max:30'],
            'companyName' => ['nullable', 'string', 'max:150'],
            'jobTitle' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'provinceId' => [
                'nullable',
                'integer',
                Rule::exists('provinces', 'id')->where(
                    fn (Builder $query): Builder => $query->where('is_active', true),
                ),
            ],
            'wardId' => [
                'nullable',
                'integer',
                Rule::exists('wards', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('is_active', true)
                        ->where('province_id', $this->nullableId($this->provinceId) ?? 0),
                ),
            ],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', Rule::enum(LeadPriority::class)],
            'estimatedValue' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'tagIds' => ['array', 'max:20'],
            'tagIds.*' => [
                'integer',
                'distinct',
                Rule::exists('tags', 'id')->where(
                    fn (Builder $query): Builder => $query->where('is_active', true),
                ),
            ],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'sourceId' => 'nguồn Lead',
            'ownerId' => 'người phụ trách',
            'fullName' => 'họ và tên',
            'email' => 'email',
            'phone' => 'số điện thoại',
            'secondaryPhone' => 'số điện thoại phụ',
            'companyName' => 'công ty',
            'jobTitle' => 'chức danh',
            'website' => 'website',
            'address' => 'địa chỉ',
            'provinceId' => 'tỉnh/thành phố',
            'wardId' => 'phường/xã',
            'city' => 'thành phố',
            'province' => 'tỉnh/thành',
            'country' => 'quốc gia',
            'priority' => 'mức ưu tiên',
            'estimatedValue' => 'giá trị dự kiến',
            'notes' => 'ghi chú',
            'tagIds' => 'tag',
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'fullName.required' => 'Vui lòng nhập họ và tên Lead.',
            'email.lowercase' => 'Email phải được viết bằng chữ thường.',
            'email.email' => 'Email không đúng định dạng.',
            'website.url' => 'Website phải là URL bắt đầu bằng http:// hoặc https://.',
            'provinceId.exists' => 'Tỉnh/Thành phố không tồn tại hoặc đã ngừng sử dụng.',
            'wardId.exists' => 'Phường/Xã không thuộc Tỉnh/Thành phố đã chọn.',
            'sourceId.exists' => 'Nguồn Lead không tồn tại hoặc đã ngừng hoạt động.',
            'ownerId.exists' => 'Người phụ trách không tồn tại hoặc đã bị khóa.',
            'priority.enum' => 'Mức ưu tiên không hợp lệ.',
            'estimatedValue.numeric' => 'Giá trị dự kiến phải là một số.',
            'estimatedValue.min' => 'Giá trị dự kiến không được âm.',
            'estimatedValue.max' => 'Giá trị dự kiến vượt quá giới hạn lưu trữ.',
            'tagIds.max' => 'Mỗi Lead chỉ được chọn tối đa 20 tag.',
            'tagIds.*.distinct' => 'Danh sách tag không được trùng lặp.',
            'tagIds.*.exists' => 'Tag được chọn không tồn tại hoặc đã ngừng hoạt động.',
        ];
    }

    /** @return array<string, mixed> */
    public function validatedPayload(): array
    {
        $this->normalize();
        $validated = $this->validate();

        $payload = [
            'lead_source_id' => $this->nullableId($validated['sourceId']),
            'owner_id' => $this->nullableId($validated['ownerId']),
            'full_name' => $validated['fullName'],
            'email' => $this->nullableString($validated['email']),
            'phone' => $this->nullableString($validated['phone']),
            'secondary_phone' => $this->nullableString($validated['secondaryPhone']),
            'company_name' => $this->nullableString($validated['companyName']),
            'job_title' => $this->nullableString($validated['jobTitle']),
            'website' => $this->nullableString($validated['website']),
            'address' => $this->nullableString($validated['address']),
            'city' => $this->nullableString($validated['city']),
            'province' => $this->nullableString($validated['province']),
            'country' => $this->nullableString($validated['country']),
            'priority' => LeadPriority::from($validated['priority']),
            'estimated_value' => $this->nullableString($validated['estimatedValue']),
            'notes' => $this->nullableString($validated['notes']),
            'tag_ids' => collect($validated['tagIds'])
                ->map(static fn (int|string $id): int => (int) $id)
                ->unique()
                ->values()
                ->all(),
        ];

        if ($this->leadId === null
            || $this->administrativeUnitSelectionChanged
            || filled($this->provinceId)
            || filled($this->wardId)) {
            $payload['province_id'] = $this->nullableId($validated['provinceId']);
            $payload['ward_id'] = $this->nullableId($validated['wardId']);
        }

        return $payload;
    }

    public function fillFrom(Lead $lead): void
    {
        $this->leadId = $lead->getKey();
        $this->sourceId = $lead->lead_source_id === null ? '' : (string) $lead->lead_source_id;
        $this->ownerId = $lead->owner_id === null ? '' : (string) $lead->owner_id;
        $this->fullName = $lead->full_name;
        $this->email = $lead->email ?? '';
        $this->phone = $lead->phone ?? '';
        $this->secondaryPhone = $lead->secondary_phone ?? '';
        $this->companyName = $lead->company_name ?? '';
        $this->jobTitle = $lead->job_title ?? '';
        $this->website = $lead->website ?? '';
        $this->address = $lead->address ?? '';
        $this->provinceId = $lead->province_id === null ? null : (string) $lead->province_id;
        $this->wardId = $lead->ward_id === null ? null : (string) $lead->ward_id;
        $this->city = $lead->city ?? '';
        $this->province = $lead->province ?? '';
        $this->country = $lead->country ?? '';
        $priority = $lead->getAttribute('priority');
        $this->priority = $priority instanceof LeadPriority ? $priority->value : (string) $priority;
        $this->estimatedValue = $lead->estimated_value ?? '';
        $this->notes = $lead->notes ?? '';
        $this->tagIds = $lead->tags->modelKeys();
        $this->administrativeUnitSelectionChanged = false;
    }

    private function normalize(): void
    {
        foreach ([
            'fullName', 'email', 'phone', 'secondaryPhone', 'companyName', 'jobTitle',
            'website', 'address', 'city', 'province', 'country', 'estimatedValue', 'notes',
        ] as $property) {
            $this->{$property} = trim($this->{$property});
        }

        $this->email = Str::lower($this->email);
    }

    private function nullableId(mixed $value): ?int
    {
        return $value === '' || $value === null ? null : (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === '' || $value === null ? null : (string) $value;
    }
}
