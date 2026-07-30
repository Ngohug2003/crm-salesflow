<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Services\LeadScoringService;
use App\Support\LeadContactNormalizer;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ?int $lead_source_id
 * @property ?int $owner_id
 * @property ?int $department_id
 * @property string $full_name
 * @property ?string $email
 * @property ?string $phone
 * @property ?string $secondary_phone
 * @property ?string $company_name
 * @property ?string $job_title
 * @property ?string $website
 * @property ?string $address
 * @property ?int $province_id
 * @property ?int $ward_id
 * @property ?string $city
 * @property ?string $province
 * @property ?string $country
 * @property LeadStatus $status
 * @property LeadPriority $priority
 * @property ?string $estimated_value
 * @property int $score
 * @property ?string $notes
 * @property ?Carbon $converted_at
 * @property string $score_level
 * @property string $score_badge_color
 * @property string $score_level_label
 */
final class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'lead_source_id',
        'owner_id',
        'department_id',
        'full_name',
        'email',
        'phone',
        'secondary_phone',
        'company_name',
        'job_title',
        'website',
        'address',
        'province_id',
        'ward_id',
        'city',
        'province',
        'country',
        'status',
        'priority',
        'estimated_value',
        'score',
        'notes',
        'converted_at',
        'sla_first_touch_due_at',
        'sla_satisfied_at',
        'is_sla_overdue',
        'sla_reminder_sent_at',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        self::saving(function (Lead $lead): void {
            $lead->setAttribute('email_normalized', LeadContactNormalizer::email($lead->email));
            $lead->setAttribute('phone_normalized', LeadContactNormalizer::phone($lead->phone));
            $lead->setAttribute(
                'secondary_phone_normalized',
                LeadContactNormalizer::phone($lead->secondary_phone),
            );
            $lead->setAttribute(
                'score',
                app(LeadScoringService::class)->calculateScore($lead),
            );
        });
    }

    public function getScoreLevelAttribute(): string
    {
        if ($this->score >= 70) {
            return 'hot';
        }
        if ($this->score >= 40) {
            return 'warm';
        }

        return 'cold';
    }

    public function getScoreBadgeColorAttribute(): string
    {
        return match ($this->score_level) {
            'hot' => 'emerald',
            'warm' => 'amber',
            default => 'slate',
        };
    }

    public function getScoreLevelLabelAttribute(): string
    {
        return match ($this->score_level) {
            'hot' => 'Hot Lead',
            'warm' => 'Warm Lead',
            default => 'Cold Lead',
        };
    }

    /** @return BelongsTo<LeadSource, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<Province, $this> */
    public function provinceUnit(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    /** @return BelongsTo<Ward, $this> */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_id');
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

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /** @return HasMany<LeadAssignmentHistory, $this> */
    public function assignmentHistories(): HasMany
    {
        return $this->hasMany(LeadAssignmentHistory::class);
    }

    /** @return HasMany<LeadStatusHistory, $this> */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(LeadStatusHistory::class);
    }

    /** @return MorphMany<Activity, $this> */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    /** @return MorphMany<Task, $this> */
    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'subject');
    }

    /** @return HasMany<LeadRoutingExecution, $this> */
    public function routingExecutions(): HasMany
    {
        return $this->hasMany(LeadRoutingExecution::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'priority' => LeadPriority::class,
            'estimated_value' => 'decimal:2',
            'score' => 'integer',
            'converted_at' => 'datetime',
            'sla_first_touch_due_at' => 'datetime',
            'sla_satisfied_at' => 'datetime',
            'is_sla_overdue' => 'boolean',
            'sla_reminder_sent_at' => 'datetime',
        ];
    }
}
