<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Support\LeadContactNormalizer;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'city',
        'province',
        'country',
        'status',
        'priority',
        'estimated_value',
        'notes',
        'converted_at',
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
        });
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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'priority' => LeadPriority::class,
            'estimated_value' => 'decimal:2',
            'converted_at' => 'datetime',
        ];
    }
}
