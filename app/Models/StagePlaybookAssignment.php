<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StagePlaybookAssignment extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'pipeline_stage_id',
        'sales_playbook_id',
        'assigned_by',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsTo<PipelineStage, $this> */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    /** @return BelongsTo<SalesPlaybook, $this> */
    public function playbook(): BelongsTo
    {
        return $this->belongsTo(SalesPlaybook::class, 'sales_playbook_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
