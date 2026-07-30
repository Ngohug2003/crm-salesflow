<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $lead_id
 * @property int|null $rule_id
 * @property int|null $assigned_user_id
 * @property string $status
 * @property array<int>|null $candidate_user_ids
 * @property string|null $reason
 * @property Carbon|null $sla_due_at
 * @property int|null $executed_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Lead $lead
 * @property-read LeadRoutingRule|null $rule
 * @property-read User|null $assignedUser
 * @property-read User|null $executor
 */
final class LeadRoutingExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'rule_id',
        'assigned_user_id',
        'status',
        'candidate_user_ids',
        'reason',
        'sla_due_at',
        'executed_by',
    ];

    protected function casts(): array
    {
        return [
            'candidate_user_ids' => 'array',
            'sla_due_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Lead, $this> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /** @return BelongsTo<LeadRoutingRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(LeadRoutingRule::class, 'rule_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
