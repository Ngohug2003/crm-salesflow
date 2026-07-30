<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ForecastCategory;
use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $title
 * @property string $code
 * @property float|string $amount
 * @property int $pipeline_id
 * @property int $stage_id
 * @property ForecastCategory $forecast_category
 * @property bool $is_won
 * @property bool $is_lost
 * @property-read OpportunityRiskSnapshot|null $currentRiskSnapshot
 * @property-read OpportunityRiskAcknowledgement|null $latestRiskAcknowledgement
 */
final class Opportunity extends Model
{
    /** @use HasFactory<OpportunityFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'title',
        'code',
        'amount',
        'pipeline_id',
        'stage_id',
        'forecast_category',
        'company_id',
        'contact_id',
        'lead_id',
        'owner_id',
        'department_id',
        'created_by',
        'updated_by',
        'expected_close_date',
        'actual_close_date',
        'lost_reason',
        'notes',
        'is_won',
        'is_lost',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'forecast_category' => ForecastCategory::class,
            'is_won' => 'boolean',
            'is_lost' => 'boolean',
            'expected_close_date' => 'date',
            'actual_close_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Tinh gia tri du bao weighted value = amount * probability / 100
     */
    public function getWeightedValueAttribute(): float
    {
        $probability = $this->stage !== null ? $this->stage->probability : 0;
        $amount = (float) $this->amount;

        return round(($amount * $probability) / 100, 2);
    }

    /** @return BelongsTo<Pipeline, $this> */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class, 'pipeline_id');
    }

    /** @return BelongsTo<PipelineStage, $this> */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    /** @return BelongsTo<Lead, $this> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /** @return HasMany<OpportunityItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OpportunityItem::class, 'opportunity_id');
    }

    /** @return HasMany<OpportunityStageHistory, $this> */
    public function stageHistories(): HasMany
    {
        return $this->hasMany(OpportunityStageHistory::class, 'opportunity_id')->orderBy('created_at', 'desc');
    }

    /** @return MorphMany<Activity, $this> */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    /** @return HasManyThrough<OpportunityRiskAcknowledgement, OpportunityRiskSnapshot, $this> */
    public function riskAcknowledgements(): HasManyThrough
    {
        return $this->hasManyThrough(
            OpportunityRiskAcknowledgement::class,
            OpportunityRiskSnapshot::class,
            'opportunity_id',
            'opportunity_risk_snapshot_id'
        );
    }

    /** @return HasOneThrough<OpportunityRiskAcknowledgement, OpportunityRiskSnapshot, $this> */
    public function latestRiskAcknowledgement(): HasOneThrough
    {
        return $this->hasOneThrough(
            OpportunityRiskAcknowledgement::class,
            OpportunityRiskSnapshot::class,
            'opportunity_id',
            'opportunity_risk_snapshot_id'
        )->latestOfMany('acted_at');
    }

    /** @return MorphMany<Task, $this> */
    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'subject');
    }

    /** @return HasMany<OpportunityPlaybookRun, $this> */
    public function playbookRuns(): HasMany
    {
        return $this->hasMany(OpportunityPlaybookRun::class);
    }

    /** @return HasMany<OpportunityRiskSnapshot, $this> */
    public function riskSnapshots(): HasMany
    {
        return $this->hasMany(OpportunityRiskSnapshot::class)->latest('evaluated_at');
    }

    /** @return HasOne<OpportunityRiskSnapshot, $this> */
    public function currentRiskSnapshot(): HasOne
    {
        return $this->hasOne(OpportunityRiskSnapshot::class)->where('is_current', true)->latestOfMany('evaluated_at');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeWon(Builder $query): Builder
    {
        return $query->where('is_won', true);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeLost(Builder $query): Builder
    {
        return $query->where('is_lost', true);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_won', false)->where('is_lost', false);
    }
}
