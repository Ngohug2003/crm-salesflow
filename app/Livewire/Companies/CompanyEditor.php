<?php

declare(strict_types=1);

namespace App\Livewire\Companies;

use App\Exceptions\DuplicateCompanyException;
use App\Models\Company;
use App\Models\Province;
use App\Models\User;
use App\Models\Ward;
use App\Services\AdministrativeUnitService;
use App\Services\CompanyManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class CompanyEditor extends Component
{
    public ?int $companyId = null;

    public string $name = '';

    public string $taxCode = '';

    public string $website = '';

    public string $email = '';

    public string $phone = '';

    public string $industry = '';

    public string $companySize = '';

    public string $annualRevenue = '';

    public string $address = '';

    public ?string $provinceId = null;

    public ?string $wardId = null;

    public string $city = '';

    public string $province = '';

    public string $country = 'Việt Nam';

    public string $notes = '';

    public string $ownerId = '';

    /** @var list<array{id: int, name: string, tax_code: ?string, email: ?string, phone: ?string, owner: string, matched_fields: list<string>}> */
    public array $duplicateCandidates = [];

    public ?string $pendingDuplicateSignature = null;

    public ?string $confirmedDuplicateSignature = null;

    public bool $showDuplicateWarning = false;

    public string $duplicateOverrideReason = '';

    public bool $administrativeUnitSelectionChanged = false;

    public function mount(?int $companyId = null): void
    {
        $this->companyId = $companyId;

        /** @var User $actor */
        $actor = Auth::user();

        if ($companyId === null) {
            Gate::forUser($actor)->authorize('create', Company::class);

            if (! $actor->hasAnyRole(['super-admin', 'admin'])) {
                $this->ownerId = (string) $actor->getKey();
            }
        } else {
            /** @var CompanyManagementService $service */
            $service = app(CompanyManagementService::class);
            $company = $service->get($actor, $companyId);
            Gate::forUser($actor)->authorize('update', $company);

            $this->name = $company->name;
            $this->taxCode = (string) $company->tax_code;
            $this->website = (string) $company->website;
            $this->email = (string) $company->email;
            $this->phone = (string) $company->phone;
            $this->industry = (string) $company->industry;
            $this->companySize = (string) $company->company_size;
            $this->annualRevenue = $company->annual_revenue ? (string) $company->annual_revenue : '';
            $this->address = (string) $company->address;
            $this->provinceId = $company->province_id ? (string) $company->province_id : null;
            $this->wardId = $company->ward_id ? (string) $company->ward_id : null;
            $this->city = (string) $company->city;
            $this->province = (string) $company->province;
            $this->country = $company->country ?: 'Việt Nam';
            $this->notes = (string) $company->notes;
            $this->ownerId = $company->owner_id ? (string) $company->owner_id : '';
        }
    }

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'taxCode' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'industry' => ['nullable', 'string', 'max:255'],
            'companySize' => ['nullable', 'string', 'max:255'],
            'annualRevenue' => ['nullable', 'numeric', 'min:0'],
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
                        ->where('province_id', filled($this->provinceId) ? (int) $this->provinceId : 0),
                ),
            ],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'ownerId' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function save(): mixed
    {
        $this->validate();

        /** @var User $actor */
        $actor = Auth::user();

        /** @var CompanyManagementService $service */
        $service = app(CompanyManagementService::class);

        $data = [
            'name' => trim($this->name),
            'tax_code' => trim($this->taxCode) !== '' ? trim($this->taxCode) : null,
            'website' => trim($this->website) !== '' ? trim($this->website) : null,
            'email' => trim($this->email) !== '' ? trim($this->email) : null,
            'phone' => trim($this->phone) !== '' ? trim($this->phone) : null,
            'industry' => trim($this->industry) !== '' ? trim($this->industry) : null,
            'company_size' => trim($this->companySize) !== '' ? trim($this->companySize) : null,
            'annual_revenue' => trim($this->annualRevenue) !== '' ? (float) $this->annualRevenue : null,
            'address' => trim($this->address) !== '' ? trim($this->address) : null,
            'city' => trim($this->city) !== '' ? trim($this->city) : null,
            'province' => trim($this->province) !== '' ? trim($this->province) : null,
            'country' => trim($this->country) !== '' ? trim($this->country) : 'Việt Nam',
            'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
            'owner_id' => $this->ownerId !== '' ? (int) $this->ownerId : null,
        ];

        if ($this->companyId === null
            || $this->administrativeUnitSelectionChanged
            || filled($this->provinceId)
            || filled($this->wardId)) {
            $data['province_id'] = filled($this->provinceId) ? (int) $this->provinceId : null;
            $data['ward_id'] = filled($this->wardId) ? (int) $this->wardId : null;
        }

        try {
            if ($this->companyId === null) {
                $company = $service->create(
                    $actor,
                    $data,
                    $this->confirmedDuplicateSignature,
                    $this->duplicateOverrideReason,
                );
                session()->flash('message', 'Tạo mới Doanh nghiệp thành công.');

                return $this->redirect(route('companies.show', $company), navigate: true);
            } else {
                $company = $service->update(
                    $actor,
                    $this->companyId,
                    $data,
                    $this->confirmedDuplicateSignature,
                    $this->duplicateOverrideReason,
                );
                session()->flash('message', 'Cập nhật Doanh nghiệp thành công.');

                return $this->redirect(route('companies.show', $company), navigate: true);
            }
        } catch (DuplicateCompanyException $e) {
            $this->duplicateCandidates = $e->candidates;
            $this->pendingDuplicateSignature = $e->signature;
            $this->showDuplicateWarning = true;

            if ($this->confirmedDuplicateSignature !== $e->signature) {
                $this->confirmedDuplicateSignature = null;
            }

            return null;
        }
    }

    public function confirmDuplicateSave(): mixed
    {
        $this->duplicateOverrideReason = trim($this->duplicateOverrideReason);
        if (empty($this->duplicateOverrideReason)) {
            $this->addError('duplicateOverrideReason', 'Vui lòng nhập lý do lưu trùng lặp.');

            return null;
        }

        if (mb_strlen($this->duplicateOverrideReason) < 10) {
            $this->addError('duplicateOverrideReason', 'Lý do phải có ít nhất 10 ký tự.');

            return null;
        }

        $this->confirmedDuplicateSignature = $this->pendingDuplicateSignature;
        $this->showDuplicateWarning = false;

        return $this->save();
    }

    public function dismissDuplicateWarning(): void
    {
        $this->duplicateCandidates = [];
        $this->pendingDuplicateSignature = null;
        $this->confirmedDuplicateSignature = null;
        $this->duplicateOverrideReason = '';
        $this->showDuplicateWarning = false;
        $this->resetErrorBag('duplicateOverrideReason');
    }

    public function updatedProvinceId(): void
    {
        $this->wardId = null;
        $this->administrativeUnitSelectionChanged = true;
        $this->resetValidation(['provinceId', 'wardId']);
    }

    public function updatedWardId(): void
    {
        $this->administrativeUnitSelectionChanged = true;
        $this->resetValidation('wardId');
    }

    /** @return Collection<int, Province> */
    #[Computed]
    public function provinceOptions(): Collection
    {
        return $this->administrativeUnits()->provinceOptions();
    }

    /** @return Collection<int, Ward> */
    #[Computed]
    public function wardOptions(): Collection
    {
        return $this->administrativeUnits()->wardOptions(
            filled($this->provinceId) ? (int) $this->provinceId : null,
        );
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        return User::query()->where('is_active', true)->orderBy('name')->get();
    }

    /** @return list<string> */
    #[Computed]
    public function industries(): array
    {
        return ['Công nghệ thông tin', 'Tài chính - Ngân hàng', 'Bất động sản', 'Sản xuất', 'Bán lẻ', 'Y tế - Dược phẩm', 'Giáo dục'];
    }

    /** @return list<string> */
    #[Computed]
    public function sizes(): array
    {
        return ['1-10 nhân sự', '11-50 nhân sự', '51-200 nhân sự', '201-500 nhân sự', '500+ nhân sự'];
    }

    public function render(): View
    {
        return view('livewire.companies.company-editor');
    }

    private function administrativeUnits(): AdministrativeUnitService
    {
        return app(AdministrativeUnitService::class);
    }
}
