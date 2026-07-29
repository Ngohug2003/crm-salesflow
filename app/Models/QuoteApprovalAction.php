<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class QuoteApprovalAction extends Model
{
    /** @var list<string> */
    protected $fillable = ['approval_request_id', 'actor_id', 'action', 'reason', 'metadata'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    /** @return BelongsTo<QuoteApprovalRequest, $this> */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteApprovalRequest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
