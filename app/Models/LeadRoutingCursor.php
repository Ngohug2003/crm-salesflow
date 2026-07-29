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
 * @property int|null $last_assigned_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read LeadRoutingRule $rule
 * @property-read User|null $lastAssignedUser
 */
final class LeadRoutingCursor extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_id',
        'last_assigned_user_id',
    ];

    /** @return BelongsTo<LeadRoutingRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(LeadRoutingRule::class, 'rule_id');
    }

    /** @return BelongsTo<User, $this> */
    public function lastAssignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_assigned_user_id');
    }
}
