<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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
}
