<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteCustomerResponse;
use App\Models\QuotePublicLink;
use App\Services\QuotePublicLinkService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class PublicQuoteViewer extends Component
{
    public string $token = '';

    public bool $isInvalid = false;

    public ?int $publicLinkId = null;

    public ?int $quoteId = null;

    public bool $requiresAccessCode = false;

    public bool $isAccessGranted = false;

    public string $accessCode = '';

    // Customer Form State
    public string $signerName = '';

    public string $signerEmail = '';

    public string $signerTitle = '';

    public string $feedbackNotes = '';

    public string $activeAction = ''; // 'accept', 'decline', 'comment'

    public bool $showResponseModal = false;

    public bool $isSubmitted = false;

    public ?string $submittedResponseType = null;

    public function mount(string $token, Request $request, QuotePublicLinkService $linkService): void
    {
        $this->token = $token;
        $link = $linkService->resolveLinkFromToken($token);

        if ($link === null) {
            $this->isInvalid = true;

            return;
        }

        $this->publicLinkId = $link->id;
        $this->quoteId = $link->quote_id;
        $this->requiresAccessCode = $link->access_code_hash !== null;
        $this->isAccessGranted = ! $this->requiresAccessCode
            || (bool) session()->get($linkService->accessSessionKey($link->id), false);

        if (! $this->isAccessGranted) {
            return;
        }

        $linkService->recordView($link, $request->ip(), $request->userAgent());

        // Check if customer already submitted a response for this link
        /** @var QuoteCustomerResponse|null $existingResponse */
        $existingResponse = $link->finalResponse;
        if ($existingResponse !== null) {
            $this->isSubmitted = true;
            $this->submittedResponseType = $existingResponse->response_type;
            $this->signerName = $existingResponse->signer_name;
            $this->signerEmail = $existingResponse->signer_email;
            $this->signerTitle = $existingResponse->signer_title ?? '';
            $this->feedbackNotes = $existingResponse->feedback_notes ?? '';
        }
    }

    public function openResponseModal(string $action): void
    {
        if ($this->isSubmitted || ! in_array($action, ['accept', 'decline', 'comment'], true)) {
            return;
        }

        $this->activeAction = $action;
        $this->showResponseModal = true;
    }

    public function submitResponse(Request $request, QuotePublicLinkService $linkService): void
    {
        if ($this->isSubmitted || $this->publicLinkId === null || ! $this->isAccessGranted) {
            return;
        }

        $this->validate([
            'signerName' => ['required', 'string', 'max:150'],
            'signerEmail' => ['required', 'email', 'max:150'],
            'signerTitle' => ['nullable', 'string', 'max:150'],
            'feedbackNotes' => [$this->activeAction === 'decline' ? 'required' : 'nullable', 'string', 'max:2000'],
        ]);

        if (! in_array($this->activeAction, ['accept', 'decline', 'comment'], true)) {
            throw ValidationException::withMessages(['response' => 'Loại phản hồi không hợp lệ.']);
        }
        $this->ensureRateLimit('response', $request);
        $link = $linkService->resolveLinkFromToken($this->token);
        if ($link === null || $link->id !== $this->publicLinkId) {
            $this->isInvalid = true;

            return;
        }

        $response = $linkService->recordResponse(
            $link,
            $this->activeAction,
            [
                'signer_name' => $this->signerName,
                'signer_email' => $this->signerEmail,
                'signer_title' => $this->signerTitle,
                'feedback_notes' => $this->feedbackNotes,
            ],
            $request->ip(),
            $request->userAgent()
        );

        if (in_array($response->response_type, ['accept', 'decline'], true)) {
            $this->isSubmitted = true;
            $this->submittedResponseType = $response->response_type;
        }
        $this->showResponseModal = false;

        $message = match ($this->activeAction) {
            'accept' => 'Cảm ơn bạn! Yêu cầu chấp thuận báo giá đã được xác nhận thành công.',
            'decline' => 'Phản hồi từ chối của bạn đã được gửi tới đội ngũ kinh doanh.',
            default => 'Ý kiến phản hồi của bạn đã được gửi thành công.',
        };

        session()->flash('success', $message);
    }

    public function submitAccessCode(Request $request, QuotePublicLinkService $linkService): void
    {
        $this->validate(['accessCode' => ['required', 'string', 'max:100']]);
        $this->ensureRateLimit('access-code', $request);
        $link = $linkService->resolveLinkFromToken($this->token);

        if ($link === null || ! $linkService->verifyAccessCode($link, $this->accessCode)) {
            throw ValidationException::withMessages(['accessCode' => 'Mã truy cập không đúng hoặc đường dẫn không còn hiệu lực.']);
        }

        session()->put($linkService->accessSessionKey($link->id), true);
        $this->accessCode = '';
        $this->isAccessGranted = true;
        $linkService->recordView($link, $request->ip(), $request->userAgent());
    }

    public function render(): View
    {
        if ($this->isInvalid || $this->publicLinkId === null) {
            return view('livewire.public.public-quote-invalid')
                ->layout('layouts.guest', ['title' => 'Đường dẫn không hợp lệ — SalesFlow CRM']);
        }

        if (! $this->isAccessGranted) {
            return view('livewire.public.public-quote-access-code')
                ->layout('layouts.guest', ['title' => 'Mã truy cập báo giá — SalesFlow CRM']);
        }

        /** @var QuotePublicLink $link */
        $link = QuotePublicLink::query()
            ->with('quote')
            ->findOrFail($this->publicLinkId);

        $quote = $this->presentSnapshot($link);

        return view('livewire.public.public-quote-viewer', [
            'link' => $link,
            'quote' => $quote,
        ])->layout('layouts.guest', ['title' => "Báo giá #{$quote->quote_number} — {$quote->company?->name}"]);
    }

    private function ensureRateLimit(string $action, Request $request): void
    {
        $key = "public-quote:{$action}:{$this->publicLinkId}:{$request->ip()}";
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['response' => 'Bạn đã thao tác quá nhiều lần. Vui lòng thử lại sau một phút.']);
        }

        RateLimiter::hit($key, 60);
    }

    private function presentSnapshot(QuotePublicLink $link): Quote
    {
        $snapshot = $link->public_snapshot ?? [];
        $quoteData = (array) data_get($snapshot, 'quote', []);
        $quote = new Quote;
        $quote->forceFill([
            'quote_number' => $quoteData['number'] ?? '',
            'valid_until' => $quoteData['valid_until'] ?? null,
            'subtotal' => $quoteData['subtotal'] ?? 0,
            'discount_amount' => $quoteData['discount_amount'] ?? 0,
            'tax_percent' => $quoteData['tax_percent'] ?? 0,
            'tax_amount' => $quoteData['tax_amount'] ?? 0,
            'total_amount' => $quoteData['total_amount'] ?? 0,
            'notes' => $quoteData['notes'] ?? null,
            'issued_at' => $quoteData['issued_at'] ?? null,
            'status' => QuoteStatus::Issued,
        ]);
        $quote->setRelation('company', (object) array_merge([
            'name' => null,
            'tax_code' => null,
            'address' => null,
            'email' => null,
            'phone' => null,
        ], (array) data_get($snapshot, 'company', [])));
        $quote->setRelation('contact', (object) array_merge([
            'name' => null,
            'email' => null,
            'phone' => null,
        ], (array) data_get($snapshot, 'contact', [])));
        $quote->setRelation('creator', (object) array_merge([
            'name' => null,
            'email' => null,
        ], (array) data_get($snapshot, 'creator', [])));
        $quote->setRelation('items', collect(data_get($snapshot, 'items', []))->map(static fn (array $item): object => (object) [
            'product_name' => $item['product_name'] ?? '',
            'description' => $item['notes'] ?? null,
            'quantity' => $item['quantity'] ?? 0,
            'unit_price' => $item['unit_price'] ?? 0,
            'subtotal' => $item['total_price'] ?? 0,
        ]));

        return $quote;
    }
}
