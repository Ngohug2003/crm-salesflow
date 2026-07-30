<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityType;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ActivityType $activity_type
 * @property string $subject_type
 * @property int $subject_id
 * @property string $title
 * @property string|null $description
 * @property int $user_id
 * @property Carbon|null $performed_at
 * @property int|null $duration_minutes
 * @property string|null $location
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Model|null $subject
 * @property User|null $user
 * @property User|null $creator
 */
final class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'activity_type',
        'subject_type',
        'subject_id',
        'title',
        'description',
        'user_id',
        'performed_at',
        'duration_minutes',
        'location',
        'created_by',
        'updated_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'activity_type' => ActivityType::class,
            'performed_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
