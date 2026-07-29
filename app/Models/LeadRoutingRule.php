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
 * @property string $name
 * @property string $strategy
 * @property int $priority
 * @property bool $is_active
 * @property int|null $department_id
 * @property int|null $target_role_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Department|null $department
 * @property-read Collection<int, LeadRoutingRuleCondition> $conditions
 * @property-read LeadRoutingCursor|null $cursor
 */
final class LeadRoutingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'strategy',
        'priority',
        'is_active',
        'department_id',
        'target_role_id',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return HasMany<LeadRoutingRuleCondition, $this> */
    public function conditions(): HasMany
    {
        return $this->hasMany(LeadRoutingRuleCondition::class, 'rule_id');
    }

    /** @return HasOne<LeadRoutingCursor, $this> */
    public function cursor(): HasOne
    {
        return $this->hasOne(LeadRoutingCursor::class, 'rule_id');
    }
}
