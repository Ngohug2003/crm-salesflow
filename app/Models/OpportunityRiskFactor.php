<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $opportunity_risk_snapshot_id
 * @property string $code
 * @property string $title
 * @property int $points
 * @property string $recommended_action
 * @property array<string, mixed>|null $details
 * @property-read OpportunityRiskSnapshot $snapshot
 */
final class OpportunityRiskFactor extends Model
{
    use HasFactory;

    protected $table = 'opportunity_risk_factors';

    protected $fillable = [
        'opportunity_risk_snapshot_id',
        'code',
        'title',
        'points',
        'recommended_action',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'details' => 'array',
        ];
    }

    /** @return BelongsTo<OpportunityRiskSnapshot, $this> */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(OpportunityRiskSnapshot::class, 'opportunity_risk_snapshot_id');
    }
}
