<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Quote;
use App\Models\QuoteApprovalRequest;
use App\Models\QuoteApprovalRule;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface QuoteRepository
{
    public function findForUpdate(int $quoteId): Quote;

    public function matchingApprovalRule(Quote $quote): ?QuoteApprovalRule;

    /** @return LengthAwarePaginator<int, QuoteApprovalRequest> */
    public function approvalInbox(User $actor, string $status, int $perPage = 15): LengthAwarePaginator;
}
