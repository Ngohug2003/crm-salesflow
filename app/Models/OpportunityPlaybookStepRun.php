<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SalesPlaybookStepType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $position
 * @property SalesPlaybookStepType $type
 * @property string $title
 * @property string|null $instructions
 * @property array<string, mixed>|null $configuration
 * @property bool $is_required
 * @property bool $blocks_stage_exit
 * @property string $status
 * @property string|null $response_text
 */
final class OpportunityPlaybookStepRun extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'opportunity_playbook_run_id',
        'sales_playbook_step_id',
        'task_id',
        'completed_by',
        'position',
        'type',
        'title',
        'instructions',
        'configuration',
        'is_required',
        'blocks_stage_exit',
        'status',
        'response_text',
        'due_at',
        'completed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'type' => SalesPlaybookStepType::class,
            'configuration' => 'array',
            'is_required' => 'boolean',
            'blocks_stage_exit' => 'boolean',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<OpportunityPlaybookRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(OpportunityPlaybookRun::class, 'opportunity_playbook_run_id');
    }

    /** @return BelongsTo<SalesPlaybookStep, $this> */
    public function sourceStep(): BelongsTo
    {
        return $this->belongsTo(SalesPlaybookStep::class, 'sales_playbook_step_id');
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** @return BelongsTo<User, $this> */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
