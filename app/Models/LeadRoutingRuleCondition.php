<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $rule_id
 * @property int|null $province_id
 * @property int|null $lead_source_id
 * @property string|null $min_estimated_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read LeadRoutingRule $rule
 * @property-read Province|null $province
 * @property-read LeadSource|null $leadSource
 */
final class LeadRoutingRuleCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_id',
        'province_id',
        'lead_source_id',
        'min_estimated_value',
    ];

    protected function casts(): array
    {
        return [
            'min_estimated_value' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<LeadRoutingRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(LeadRoutingRule::class, 'rule_id');
    }

    /** @return BelongsTo<Province, $this> */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    /** @return BelongsTo<LeadSource, $this> */
    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }
}
