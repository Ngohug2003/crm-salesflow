<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationEventCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property NotificationEventCategory $event_category
 * @property bool $channel_database
 * @property bool $channel_email
 * @property bool $channel_broadcast
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User $user
 */
final class UserNotificationPreference extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'event_category',
        'channel_database',
        'channel_email',
        'channel_broadcast',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'event_category' => NotificationEventCategory::class,
            'channel_database' => 'boolean',
            'channel_email' => 'boolean',
            'channel_broadcast' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
