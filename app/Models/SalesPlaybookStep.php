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
 * @property int|null $due_business_days
 */
final class SalesPlaybookStep extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'sales_playbook_id',
        'position',
        'type',
        'title',
        'instructions',
        'configuration',
        'is_required',
        'blocks_stage_exit',
        'due_business_days',
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
            'due_business_days' => 'integer',
        ];
    }

    /** @return BelongsTo<SalesPlaybook, $this> */
    public function playbook(): BelongsTo
    {
        return $this->belongsTo(SalesPlaybook::class, 'sales_playbook_id');
    }
}
