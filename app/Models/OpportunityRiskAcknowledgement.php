<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $opportunity_risk_snapshot_id
 * @property int $user_id
 * @property string $status
 * @property string|null $note
 * @property Carbon $acted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read OpportunityRiskSnapshot $snapshot
 * @property-read User $user
 * @property-read User $actor
 */
final class OpportunityRiskAcknowledgement extends Model
{
    use HasFactory;

    protected $table = 'opportunity_risk_acknowledgements';

    protected $fillable = [
        'opportunity_risk_snapshot_id',
        'user_id',
        'status',
        'note',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'acted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<OpportunityRiskSnapshot, $this> */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(OpportunityRiskSnapshot::class, 'opportunity_risk_snapshot_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
