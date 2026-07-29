<?php

declare(strict_types=1);

namespace App\Livewire\Quotes;

use App\Exceptions\QuoteWorkflowException;
use App\Models\Quote;
use App\Models\QuoteApprovalRule;
use App\Models\QuoteBrandingSetting;
use App\Models\User;
use App\Services\QuoteSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
final class QuoteSettings extends Component
{
    use WithFileUploads;

    public string $companyName = '';

    public string $taxCode = '';

    public string $address = '';

    public string $hotline = '';

    public string $email = '';

    public string $paymentTerms = '';

    public string $bankInformation = '';

    public ?TemporaryUploadedFile $logo = null;

    public string $currentLogoPath = '';

    /** @var list<array{id: int, name: string, minimum_discount_percent: string, maximum_discount_percent: string, required_role: string|null, auto_approve: bool}> */
    public array $rules = [];

    public ?string $errorMessage = null;

    public function mount(): void
    {
        Gate::authorize('manageSettings', Quote::class);
        $branding = QuoteBrandingSetting::query()->firstOrNew();
        $this->companyName = (string) ($branding->company_name ?: 'SalesFlow CRM');
        $this->taxCode = (string) ($branding->tax_code ?? '');
        $this->address = (string) ($branding->address ?? '');
        $this->hotline = (string) ($branding->hotline ?? '');
        $this->email = (string) ($branding->email ?? '');
        $this->paymentTerms = (string) ($branding->payment_terms ?? '');
        $this->bankInformation = (string) ($branding->bank_information ?? '');
        $this->currentLogoPath = (string) ($branding->logo_path ?? '');
        /** @var list<array{id: int, name: string, minimum_discount_percent: string, maximum_discount_percent: string, required_role: string|null, auto_approve: bool}> $rules */
        $rules = QuoteApprovalRule::query()->orderBy('priority')->get()->map(
            static fn (QuoteApprovalRule $rule): array => [
                'id' => $rule->id,
                'name' => $rule->name,
                'minimum_discount_percent' => (string) $rule->minimum_discount_percent,
                'maximum_discount_percent' => $rule->maximum_discount_percent !== null ? (string) $rule->maximum_discount_percent : '',
                'required_role' => $rule->required_role,
                'auto_approve' => (bool) $rule->auto_approve,
            ]
        )->values()->all();
        $this->rules = $rules;
    }

    public function save(QuoteSettingsService $service): void
    {
        $this->validate([
            'companyName' => ['required', 'string', 'max:255'],
            'taxCode' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'hotline' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'paymentTerms' => ['nullable', 'string', 'max:2000'],
            'bankInformation' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'rules.*.minimum_discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'rules.*.maximum_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        try {
            $service->update($this->user(), [
                'company_name' => $this->companyName,
                'tax_code' => $this->taxCode ?: null,
                'address' => $this->address ?: null,
                'hotline' => $this->hotline ?: null,
                'email' => $this->email ?: null,
                'payment_terms' => $this->paymentTerms ?: null,
                'bank_information' => $this->bankInformation ?: null,
            ], array_map(static fn (array $rule): array => [
                'id' => (int) $rule['id'],
                'minimum_discount_percent' => (string) $rule['minimum_discount_percent'],
                'maximum_discount_percent' => $rule['maximum_discount_percent'] === ''
                    ? null
                    : (string) $rule['maximum_discount_percent'],
            ], $this->rules), $this->logo);
        } catch (QuoteWorkflowException $exception) {
            $this->errorMessage = $exception->getMessage();

            return;
        }

        $this->errorMessage = null;
        $this->logo = null;
        $this->currentLogoPath = (string) (QuoteBrandingSetting::query()->value('logo_path') ?? '');
        session()->flash('success', 'Đã cập nhật cấu hình báo giá.');
    }

    public function render(): View
    {
        return view('livewire.quotes.quote-settings');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
