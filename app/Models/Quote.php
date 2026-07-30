<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $quote_number
 * @property int $opportunity_id
 * @property int|null $company_id
 * @property int|null $contact_id
 * @property QuoteStatus $status
 * @property Carbon|null $valid_until
 * @property string $subtotal
 * @property string $tax_percent
 * @property string $tax_amount
 * @property string $discount_amount
 * @property string $discount_percent
 * @property string $total_amount
 * @property int $version
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property Opportunity $opportunity
 * @property Company|null $company
 * @property Contact|null $contact
 * @property User|null $creator
 */
final class Quote extends Model
{
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'quote_number',
        'opportunity_id',
        'company_id',
        'contact_id',
        'status',
        'valid_until',
        'subtotal',
        'tax_percent',
        'tax_amount',
        'discount_amount',
        'discount_percent',
        'total_amount',
        'version',
        'submitted_at',
        'approved_at',
        'issued_at',
        'sent_at',
        'rejection_reason',
        'issued_snapshot',
        'notes',
        'created_by',
        'updated_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'version' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'issued_at' => 'datetime',
            'sent_at' => 'datetime',
            'issued_snapshot' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Opportunity, $this> */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<QuoteItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class, 'quote_id');
    }

    /** @return HasMany<QuotePublicLink, $this> */
    public function publicLinks(): HasMany
    {
        return $this->hasMany(QuotePublicLink::class, 'quote_id')->orderBy('created_at', 'desc');
    }

    /** @return HasOne<QuotePublicLink, $this> */
    public function activePublicLink(): HasOne
    {
        return $this->hasOne(QuotePublicLink::class, 'quote_id')
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latestOfMany();
    }

    /** @return HasMany<QuoteCustomerResponse, $this> */
    public function customerResponses(): HasMany
    {
        return $this->hasMany(QuoteCustomerResponse::class, 'quote_id')->orderBy('acted_at', 'desc');
    }

    /** @return HasOne<QuoteCustomerResponse, $this> */
    public function latestCustomerResponse(): HasOne
    {
        return $this->hasOne(QuoteCustomerResponse::class, 'quote_id')->latestOfMany('acted_at');
    }

    /** @return HasMany<QuoteApprovalRequest, $this> */
    public function approvalRequests(): HasMany
    {
        return $this->hasMany(QuoteApprovalRequest::class);
    }

    /** @return HasMany<QuoteVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(QuoteVersion::class);
    }

    /** @return HasMany<QuoteDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(QuoteDocument::class);
    }
}
