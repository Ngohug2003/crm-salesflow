<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $quote_id
 * @property int|null $rule_id
 * @property int $requested_by
 * @property int|null $resolved_by
 * @property int $quote_version
 * @property QuoteApprovalStatus $status
 * @property string|null $required_role
 * @property string $discount_percent
 * @property string $total_amount
 * @property string|null $request_note
 * @property string|null $decision_reason
 * @property Quote $quote
 * @property User $requester
 */
final class QuoteApprovalRequest extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'quote_id',
        'rule_id',
        'requested_by',
        'resolved_by',
        'quote_version',
        'status',
        'required_role',
        'discount_percent',
        'total_amount',
        'request_note',
        'decision_reason',
        'submitted_at',
        'resolved_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => QuoteApprovalStatus::class,
            'quote_version' => 'integer',
            'discount_percent' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /** @return BelongsTo<QuoteApprovalRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(QuoteApprovalRule::class, 'rule_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /** @return HasMany<QuoteApprovalAction, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(QuoteApprovalAction::class, 'approval_request_id');
    }
}
