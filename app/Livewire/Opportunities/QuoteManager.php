<?php

declare(strict_types=1);

namespace App\Livewire\Opportunities;

use App\Exceptions\QuoteWorkflowException;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteApprovalService;
use App\Services\QuoteDocumentService;
use App\Services\QuoteService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class QuoteManager extends Component
{
    public int $opportunityId;

    public bool $showCreateModal = false;

    public bool $showSubmitModal = false;

    public bool $showDecisionModal = false;

    public ?int $selectedQuoteId = null;

    public ?int $selectedApprovalRequestId = null;

    public string $validUntil = '';

    public string $taxPercent = '10.00';

    public string $discountAmount = '0';

    public string $notes = '';

    public string $approvalNote = '';

    public string $decision = 'approve';

    public string $decisionReason = '';

    public string $feedbackMessage = '';

    public string $errorMessage = '';

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
            ->with([
                'company', 'contact', 'items', 'creator',
                'approvalRequests' => fn ($query) => $query->latest('submitted_at'),
                'documents' => fn ($query) => $query->latest(),
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->reset('errorMessage', 'notes');
        $this->validUntil = CarbonImmutable::now()->addDays(30)->format('Y-m-d');
        $this->taxPercent = '10.00';
        $this->discountAmount = '0';
        $this->showCreateModal = true;
    }

    public function createQuote(QuoteService $service): void
    {
        $this->validate([
            'validUntil' => ['required', 'date', 'after_or_equal:today'],
            'taxPercent' => ['required', 'numeric', 'min:0', 'max:100'],
            'discountAmount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $quote = $service->createFromOpportunity($this->user(), $this->opportunityId, [
            'valid_until' => $this->validUntil,
            'tax_percent' => $this->taxPercent,
            'discount_amount' => $this->discountAmount,
            'notes' => $this->notes,
        ]);

        $this->feedbackMessage = "Đã tạo báo giá {$quote->quote_number}.";
        $this->showCreateModal = false;
    }

    public function openSubmitModal(int $quoteId): void
    {
        $this->selectedQuoteId = $quoteId;
        $this->approvalNote = '';
        $this->errorMessage = '';
        $this->showSubmitModal = true;
    }

    public function submitForApproval(QuoteApprovalService $service): void
    {
        $this->validate(['approvalNote' => ['nullable', 'string', 'max:1000']]);

        try {
            $request = $service->submit($this->user(), (int) $this->selectedQuoteId, $this->approvalNote ?: null);
            $this->feedbackMessage = $request->status->value === 'approved'
                ? 'Báo giá nằm trong hạn mức và đã được tự động phê duyệt.'
                : 'Đã gửi báo giá đến đúng cấp phê duyệt.';
            $this->showSubmitModal = false;
        } catch (QuoteWorkflowException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function openApprovalDecision(int $requestId, string $decision): void
    {
        $this->selectedApprovalRequestId = $requestId;
        $this->decision = $decision === 'reject' ? 'reject' : 'approve';
        $this->decisionReason = '';
        $this->errorMessage = '';
        $this->showDecisionModal = true;
    }

    public function resolveApprovalDecision(QuoteApprovalService $service): void
    {
        $this->validate([
            'selectedApprovalRequestId' => ['required', 'integer'],
            'decision' => ['required', 'in:approve,reject'],
            'decisionReason' => [$this->decision === 'reject' ? 'required' : 'nullable', 'string', 'max:1000'],
        ], ['decisionReason.required' => 'Vui lòng nhập lý do từ chối.']);

        try {
            if ($this->decision === 'approve') {
                $service->approve(
                    $this->user(),
                    (int) $this->selectedApprovalRequestId,
                    $this->decisionReason ?: null,
                );
                $this->feedbackMessage = 'Báo giá đã được phê duyệt.';
            } else {
                $service->reject(
                    $this->user(),
                    (int) $this->selectedApprovalRequestId,
                    $this->decisionReason,
                );
                $this->feedbackMessage = 'Đã từ chối báo giá và gửi lý do cho người lập.';
            }
        } catch (QuoteWorkflowException $exception) {
            $this->errorMessage = $exception->getMessage();

            return;
        }

        $this->showDecisionModal = false;
        $this->reset('selectedApprovalRequestId', 'decisionReason');
    }

    public function issue(int $quoteId, QuoteDocumentService $service): void
    {
        try {
            $service->issue($this->user(), $quoteId);
            $this->feedbackMessage = 'Đã phát hành báo giá. PDF đang được tạo trong hàng đợi.';
        } catch (QuoteWorkflowException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function markSent(int $quoteId, QuoteDocumentService $service): void
    {
        $service->markSent($this->user(), $quoteId);
        $this->feedbackMessage = 'Đã đánh dấu báo giá là đã gửi.';
    }

    public function deleteQuote(int $quoteId, QuoteService $service): void
    {
        $quote = Quote::query()->findOrFail($quoteId);
        $service->deleteQuote($this->user(), $quote);
        $this->feedbackMessage = "Đã xóa báo giá {$quote->quote_number}.";
    }

    public function documentUrl(int $quoteId, int $documentId): string
    {
        return URL::temporarySignedRoute(
            'quotes.documents.download',
            now()->addMinutes(15),
            ['quoteId' => $quoteId, 'documentId' => $documentId],
        );
    }

    #[Computed]
    public function administrator(): bool
    {
        /** @var list<string> $roles */
        $roles = config('crm.rbac.administrator_roles', ['super-admin', 'admin']);

        return $this->user()->hasAnyRole($roles);
    }

    public function render(): View
    {
        return view('livewire.opportunities.quote-manager');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
