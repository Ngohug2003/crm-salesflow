<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlaybookRepeatPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $opportunity_id
 * @property int $pipeline_stage_id
 * @property string $status
 * @property array<string, mixed> $playbook_snapshot
 */
final class OpportunityPlaybookRun extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'opportunity_id',
        'sales_playbook_id',
        'pipeline_stage_id',
        'started_by',
        'idempotency_key',
        'status',
        'repeat_policy',
        'playbook_snapshot',
        'started_at',
        'completed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'repeat_policy' => PlaybookRepeatPolicy::class,
            'playbook_snapshot' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Opportunity, $this> */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /** @return BelongsTo<SalesPlaybook, $this> */
    public function playbook(): BelongsTo
    {
        return $this->belongsTo(SalesPlaybook::class, 'sales_playbook_id');
    }

    /** @return BelongsTo<PipelineStage, $this> */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    /** @return HasMany<OpportunityPlaybookStepRun, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(OpportunityPlaybookStepRun::class)->orderBy('position');
    }
}
