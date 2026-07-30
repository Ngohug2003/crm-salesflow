<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OpportunityRiskLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OpportunityRiskNotificationLog extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'opportunity_id',
        'user_id',
        'level',
        'notified_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'level' => OpportunityRiskLevel::class,
            'notified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Opportunity, $this> */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
