<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteDocument;
use App\Models\QuotePublicEvent;
use App\Models\User;
use App\Services\QuoteDocumentService;
use App\Services\QuotePublicLinkService;
use App\Services\VietnameseMoneyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class QuoteController extends Controller
{
    public function show(int $quoteId): View
    {
        /** @var Quote $quote */
        $quote = Quote::query()
            ->with([
                'opportunity',
                'company',
                'contact',
                'items',
                'creator',
                'documents' => fn ($query) => $query->where('status', 'ready')->latest(),
            ])
            ->findOrFail($quoteId);

        Gate::authorize('view', $quote);
        $document = $quote->documents->first();
        $downloadUrl = $document !== null && Gate::allows('download', $quote)
            ? URL::temporarySignedRoute(
                'quotes.documents.download',
                now()->addMinutes(15),
                ['quoteId' => $quote->id, 'documentId' => $document->id],
            )
            : null;

        return view('quotes.show', [
            'quote' => $quote,
            'downloadUrl' => $downloadUrl,
        ]);
    }

    public function download(int $quoteId, int $documentId): StreamedResponse
    {
        $document = QuoteDocument::query()
            ->with(['quote.opportunity'])
            ->where('quote_id', $quoteId)
            ->findOrFail($documentId);

        Gate::authorize('download', $document->quote);
        abort_unless($document->status === 'ready', 409, 'Tài liệu PDF chưa sẵn sàng.');
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_name,
            ['Content-Type' => $document->mime_type],
        );
    }

    public function generatePublicLink(Request $request, int $quoteId, QuotePublicLinkService $linkService): RedirectResponse
    {
        /** @var Quote $quote */
        $quote = Quote::query()->findOrFail($quoteId);
        Gate::authorize('issue', $quote);

        /** @var User $user */
        $user = auth()->user();
        $validated = $request->validate([
            'access_code' => ['nullable', 'string', 'min:6', 'max:100'],
        ]);

        $result = $linkService->generateLink($quote, $user, accessCode: $validated['access_code'] ?? null);
        $publicUrl = route('quotes.public-show', ['token' => $result['plain_token']]);

        return redirect()
            ->route('quotes.show', $quote->id)
            ->with('generated_public_url', $publicUrl)
            ->with('public_link_access_protected', filled($validated['access_code'] ?? null));
    }

    public function regeneratePdf(int $quoteId, QuoteDocumentService $documentService): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $documentService->regenerate($user, $quoteId);

        return redirect()
            ->route('quotes.show', $quoteId)
            ->with('success', 'Đang tạo lại PDF theo mẫu mới. Trang sẽ có bản tải mới sau khi xử lý xong.');
    }

    public function downloadPublicPdf(
        string $token,
        QuotePublicLinkService $linkService,
        VietnameseMoneyService $money,
    ): Response {
        $link = $linkService->resolveLinkFromToken($token);
        abort_unless($link !== null, 404, 'Đường dẫn báo giá không hợp lệ hoặc đã hết hạn.');
        abort_if(
            $link->access_code_hash !== null && ! (bool) session()->get($linkService->accessSessionKey($link->id), false),
            404,
            'Đường dẫn báo giá không hợp lệ hoặc đã hết hạn.',
        );

        /** @var Quote $quote */
        $quote = $link->quote;

        QuotePublicEvent::query()->create([
            'quote_public_link_id' => $link->id,
            'event_type' => 'pdf_downloaded',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        /** @var QuoteDocument|null $readyDoc */
        $readyDoc = $quote->documents()
            ->where('status', 'ready')
            ->whereHas('version', fn ($query) => $query->where('version', $link->version_issued))
            ->latest('id')
            ->first();

        if ($readyDoc !== null && Storage::disk($readyDoc->disk)->exists($readyDoc->path)) {
            return Storage::disk($readyDoc->disk)->download(
                $readyDoc->path,
                $readyDoc->original_name,
                ['Content-Type' => $readyDoc->mime_type ?? 'application/pdf'],
            );
        }

        $snapshot = $link->public_snapshot;
        $totalInWords = $money->toWords((string) data_get($snapshot, 'quote.total_amount', '0'));

        $pdf = Pdf::loadView('pdf.quote', [
            'snapshot' => $snapshot,
            'totalInWords' => $totalInWords,
            'logoDataUri' => null,
        ])->setPaper('a4');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            "BaoGia-{$quote->quote_number}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}
