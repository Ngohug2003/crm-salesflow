<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $quote_id
 * @property int $quote_public_link_id
 * @property string $response_type
 * @property string $signer_name
 * @property string $signer_email
 * @property string|null $signer_title
 * @property string|null $feedback_notes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $acted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Quote $quote
 * @property-read QuotePublicLink $publicLink
 */
final class QuoteCustomerResponse extends Model
{
    use HasFactory;

    protected $table = 'quote_customer_responses';

    protected $fillable = [
        'quote_id',
        'quote_public_link_id',
        'response_type',
        'signer_name',
        'signer_email',
        'signer_title',
        'feedback_notes',
        'ip_address',
        'user_agent',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'acted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /** @return BelongsTo<QuotePublicLink, $this> */
    public function publicLink(): BelongsTo
    {
        return $this->belongsTo(QuotePublicLink::class, 'quote_public_link_id');
    }
}
