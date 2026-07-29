<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Quote;
use App\Models\QuoteApprovalRequest;
use App\Models\QuoteApprovalRule;
use App\Models\User;
use App\Repositories\Contracts\QuoteRepository;
use Brick\Math\BigDecimal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentQuoteRepository implements QuoteRepository
{
    public function findForUpdate(int $quoteId): Quote
    {
        return Quote::query()
            ->with(['opportunity', 'company', 'contact', 'items'])
            ->lockForUpdate()
            ->findOrFail($quoteId);
    }

    public function matchingApprovalRule(Quote $quote): ?QuoteApprovalRule
    {
        $discount = BigDecimal::of($quote->discount_percent);
        $total = BigDecimal::of($quote->total_amount);

        return QuoteApprovalRule::query()
            ->where('is_active', true)
            ->where(function ($query) use ($quote): void {
                $query->whereNull('department_id')
                    ->orWhere('department_id', $quote->opportunity->department_id);
            })
            ->orderBy('priority')
            ->orderByDesc('minimum_discount_percent')
            ->get()
            ->first(function (QuoteApprovalRule $rule) use ($discount, $total): bool {
                if ($discount->isLessThan(BigDecimal::of($rule->minimum_discount_percent))) {
                    return false;
                }

                if ($rule->maximum_discount_percent !== null
                    && $discount->isGreaterThan(BigDecimal::of($rule->maximum_discount_percent))) {
                    return false;
                }

                return $rule->minimum_total_amount === null
                    || ! $total->isLessThan(BigDecimal::of($rule->minimum_total_amount));
            });
    }

    public function approvalInbox(User $actor, string $status, int $perPage = 15): LengthAwarePaginator
    {
        return QuoteApprovalRequest::query()
            ->with(['quote.opportunity.company', 'requester', 'rule'])
            ->where('status', $status)
            ->when(! $actor->hasAnyRole(['super-admin', 'admin']), function ($query) use ($actor): void {
                $query->whereHas('quote.opportunity', function ($opportunity) use ($actor): void {
                    $opportunity->where('department_id', $actor->department_id);
                });
            })
            ->latest('submitted_at')
            ->paginate($perPage);
    }
}
