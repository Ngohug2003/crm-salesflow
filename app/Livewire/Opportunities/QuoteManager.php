<?php

declare(strict_types=1);

namespace App\Livewire\Opportunities;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class QuoteManager extends Component
{
    public int $opportunityId;

    public bool $showCreateModal = false;

    public string $validUntil = '';

    public float $taxPercent = 10.0;

    public float $discountAmount = 0.0;

    public string $notes = '';

    public string $feedbackMessage = '';

    public function mount(int $opportunityId): void
    {
        $this->opportunityId = $opportunityId;
        $this->validUntil = CarbonImmutable::now()->addDays(30)->format('Y-m-d');
    }

    /** @return EloquentCollection<int, Quote> */
    #[Computed]
    public function quotes(): EloquentCollection
    {
        return Quote::query()
            ->where('opportunity_id', $this->opportunityId)
            ->with(['company', 'contact', 'items', 'creator'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->validUntil = CarbonImmutable::now()->addDays(30)->format('Y-m-d');
        $this->taxPercent = 10.0;
        $this->discountAmount = 0.0;
        $this->notes = '';
        $this->showCreateModal = true;
    }

    public function createQuote(): void
    {
        $this->validate([
            'validUntil' => ['required', 'date'],
            'taxPercent' => ['required', 'numeric', 'min:0', 'max:100'],
            'discountAmount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $actor */
        $actor = Auth::user();

        /** @var QuoteService $service */
        $service = app(QuoteService::class);

        $quote = $service->createFromOpportunity($actor, $this->opportunityId, [
            'valid_until' => $this->validUntil,
            'tax_percent' => $this->taxPercent,
            'discount_amount' => $this->discountAmount,
            'notes' => $this->notes,
        ]);

        $this->feedbackMessage = "Đã khởi tạo thành công Báo giá mã #{$quote->quote_number}!";
        $this->showCreateModal = false;
    }

    public function changeStatus(int $quoteId, string $statusValue): void
    {
        $status = QuoteStatus::tryFrom($statusValue);
        if ($status === null) {
            return;
        }

        /** @var User $actor */
        $actor = Auth::user();
        /** @var Quote $quote */
        $quote = Quote::query()->findOrFail($quoteId);

        /** @var QuoteService $service */
        $service = app(QuoteService::class);
        $service->updateQuoteStatus($actor, $quote, $status);

        $this->feedbackMessage = "Cập nhật trạng thái Báo giá mã #{$quote->quote_number} thành '{$status->label()}'!";
    }

    public function deleteQuote(int $quoteId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var Quote $quote */
        $quote = Quote::query()->findOrFail($quoteId);

        /** @var QuoteService $service */
        $service = app(QuoteService::class);
        $service->deleteQuote($actor, $quote);

        $this->feedbackMessage = "Đã xóa Báo giá mã #{$quote->quote_number} thành công!";
    }

    public function render(): View
    {
        return view('livewire.opportunities.quote-manager');
    }
}
