<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LeadAssignmentHistory extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'lead_id',
        'previous_owner_id',
        'new_owner_id',
        'previous_department_id',
        'new_department_id',
        'changed_by',
        'reason',
    ];

    /** @return BelongsTo<Lead, $this> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /** @return BelongsTo<User, $this> */
    public function previousOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'previous_owner_id');
    }

    /** @return BelongsTo<User, $this> */
    public function newOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'new_owner_id');
    }

    /** @return BelongsTo<Department, $this> */
    public function previousDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'previous_department_id');
    }

    /** @return BelongsTo<Department, $this> */
    public function newDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'new_department_id');
    }

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
