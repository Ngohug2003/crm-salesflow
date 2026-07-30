<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $quote_id
 * @property string $token_hash
 * @property int $version_issued
 * @property array<string, mixed>|null $public_snapshot
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property string|null $access_code_hash
 * @property int $view_count
 * @property Carbon|null $last_viewed_at
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Quote $quote
 * @property-read User $creator
 * @property-read Collection<int, QuoteCustomerResponse> $responses
 * @property-read QuoteCustomerResponse|null $latestResponse
 * @property-read QuoteCustomerResponse|null $finalResponse
 */
final class QuotePublicLink extends Model
{
    use HasFactory;

    protected $table = 'quote_public_links';

    protected $fillable = [
        'quote_id',
        'token_hash',
        'version_issued',
        'public_snapshot',
        'expires_at',
        'revoked_at',
        'access_code_hash',
        'view_count',
        'last_viewed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'version_issued' => 'integer',
            'public_snapshot' => 'array',
            'view_count' => 'integer',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_viewed_at' => 'datetime',
        ];
    }

    public function isValid(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<QuoteCustomerResponse, $this> */
    public function responses(): HasMany
    {
        return $this->hasMany(QuoteCustomerResponse::class, 'quote_public_link_id');
    }

    /** @return HasOne<QuoteCustomerResponse, $this> */
    public function latestResponse(): HasOne
    {
        return $this->hasOne(QuoteCustomerResponse::class, 'quote_public_link_id')->latestOfMany('acted_at');
    }

    /** @return HasOne<QuoteCustomerResponse, $this> */
    public function finalResponse(): HasOne
    {
        return $this->hasOne(QuoteCustomerResponse::class, 'quote_public_link_id')
            ->whereIn('response_type', ['accept', 'decline'])
            ->latestOfMany('acted_at');
    }
}
