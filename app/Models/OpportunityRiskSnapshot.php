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
 * @property int $opportunity_id
 * @property string $rule_version
 * @property Carbon $evaluation_bucket
 * @property int $score
 * @property string $level
 * @property string|null $summary
 * @property bool $is_current
 * @property Carbon $evaluated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Opportunity $opportunity
 * @property-read Collection<int, OpportunityRiskFactor> $factors
 * @property-read OpportunityRiskAcknowledgement|null $latestAcknowledgement
 */
final class OpportunityRiskSnapshot extends Model
{
    use HasFactory;

    protected $table = 'opportunity_risk_snapshots';

    protected $fillable = [
        'opportunity_id',
        'rule_version',
        'evaluation_bucket',
        'score',
        'level',
        'summary',
        'is_current',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'is_current' => 'boolean',
            'evaluation_bucket' => 'datetime',
            'evaluated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Opportunity, $this> */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /** @return HasMany<OpportunityRiskFactor, $this> */
    public function factors(): HasMany
    {
        return $this->hasMany(OpportunityRiskFactor::class, 'opportunity_risk_snapshot_id');
    }

    /** @return HasMany<OpportunityRiskAcknowledgement, $this> */
    public function acknowledgements(): HasMany
    {
        return $this->hasMany(OpportunityRiskAcknowledgement::class, 'opportunity_risk_snapshot_id')->orderBy('acted_at', 'desc');
    }

    /** @return HasOne<OpportunityRiskAcknowledgement, $this> */
    public function latestAcknowledgement(): HasOne
    {
        return $this->hasOne(OpportunityRiskAcknowledgement::class, 'opportunity_risk_snapshot_id')->latestOfMany('acted_at');
    }
}
