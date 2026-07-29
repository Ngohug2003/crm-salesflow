<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class QuoteApprovalRule extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'minimum_discount_percent',
        'maximum_discount_percent',
        'minimum_total_amount',
        'department_id',
        'required_role',
        'auto_approve',
        'is_active',
        'priority',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'minimum_discount_percent' => 'decimal:2',
            'maximum_discount_percent' => 'decimal:2',
            'minimum_total_amount' => 'decimal:2',
            'auto_approve' => 'boolean',
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
