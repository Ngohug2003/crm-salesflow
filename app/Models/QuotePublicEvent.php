<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $quote_public_link_id
 * @property string $event_type
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $payload
 * @property Carbon $created_at
 * @property-read QuotePublicLink $publicLink
 */
final class QuotePublicEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'quote_public_events';

    protected $fillable = [
        'quote_public_link_id',
        'event_type',
        'ip_address',
        'user_agent',
        'payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<QuotePublicLink, $this> */
    public function publicLink(): BelongsTo
    {
        return $this->belongsTo(QuotePublicLink::class, 'quote_public_link_id');
    }
}
