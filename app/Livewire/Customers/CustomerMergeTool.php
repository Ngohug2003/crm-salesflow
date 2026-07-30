<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\CustomerMergeService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class CustomerMergeTool extends Component
{
    public string $mode = 'company';

    public ?int $masterId = null;

    public ?int $sourceId = null;

    public ?string $feedbackMessage = null;

    public function setMode(string $mode): void
    {
        if (in_array($mode, ['company', 'contact'], true)) {
            $this->mode = $mode;
            $this->masterId = null;
            $this->sourceId = null;
            $this->feedbackMessage = null;
        }
    }

    public function selectPair(int $masterId, int $sourceId): void
    {
        $this->masterId = $masterId;
        $this->sourceId = $sourceId;
        $this->feedbackMessage = null;
    }

    public function executeCompanyMerge(): void
    {
        if ($this->masterId === null || $this->sourceId === null || $this->masterId === $this->sourceId) {
            $this->addError('merge', 'Vui lòng chọn 2 doanh nghiệp khác nhau để gộp.');

            return;
        }

        /** @var User $actor */
        $actor = Auth::user();
        /** @var CustomerMergeService $service */
        $service = app(CustomerMergeService::class);

        /** @var Company $master */
        $master = Company::findOrFail($this->masterId);
        /** @var Company $source */
        $source = Company::findOrFail($this->sourceId);

        $service->mergeCompanies($actor, $master, $source);

        $this->feedbackMessage = "Đã hợp nhất Doanh nghiệp '{$source->name}' (#{$source->id}) vào '{$master->name}' (#{$master->id}) thành công.";
        $this->masterId = null;
        $this->sourceId = null;
    }

    public function executeContactMerge(): void
    {
        if ($this->masterId === null || $this->sourceId === null || $this->masterId === $this->sourceId) {
            $this->addError('merge', 'Vui lòng chọn 2 người liên hệ khác nhau để gộp.');

            return;
        }

        /** @var User $actor */
        $actor = Auth::user();
        /** @var CustomerMergeService $service */
        $service = app(CustomerMergeService::class);

        /** @var Contact $master */
        $master = Contact::findOrFail($this->masterId);
        /** @var Contact $source */
        $source = Contact::findOrFail($this->sourceId);

        $service->mergeContacts($actor, $master, $source);

        $this->feedbackMessage = "Đã hợp nhất Người liên hệ '{$source->full_name}' (#{$source->id}) vào '{$master->full_name}' (#{$master->id}) thành công.";
        $this->masterId = null;
        $this->sourceId = null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function duplicateGroups(): array
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var CustomerMergeService $service */
        $service = app(CustomerMergeService::class);

        if ($this->mode === 'company') {
            return $service->scanCompanyDuplicates($actor);
        }

        return $service->scanContactDuplicates($actor);
    }

    public function render(): View
    {
        return view('livewire.customers.customer-merge-tool', [
            'duplicateGroups' => $this->duplicateGroups(),
        ]);
    }
}
