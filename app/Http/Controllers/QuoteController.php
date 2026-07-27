<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class QuoteController extends Controller
{
    public function show(int $quoteId): View
    {
        /** @var Quote $quote */
        $quote = Quote::query()
            ->with(['opportunity', 'company', 'contact', 'items', 'creator'])
            ->findOrFail($quoteId);

        Gate::authorize('view', $quote);

        return view('quotes.show', [
            'quote' => $quote,
        ]);
    }
}
