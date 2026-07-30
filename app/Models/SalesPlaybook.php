<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlaybookRepeatPolicy;
use App\Enums\SalesPlaybookStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int $version
 * @property SalesPlaybookStatus $status
 * @property PlaybookRepeatPolicy $repeat_policy
 * @property int|null $draft_pipeline_stage_id
 */
final class SalesPlaybook extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'description',
        'version',
        'status',
        'repeat_policy',
        'draft_pipeline_stage_id',
        'created_by',
        'published_by',
        'published_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'status' => SalesPlaybookStatus::class,
            'repeat_policy' => PlaybookRepeatPolicy::class,
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /** @return HasMany<SalesPlaybookStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(SalesPlaybookStep::class)->orderBy('position');
    }

    /** @return HasMany<StagePlaybookAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(StagePlaybookAssignment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
