<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationEventCategory;
use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class NotificationPreferenceService
{
    /**
     * Get user preferences array formatted by event_category and channels.
     *
     * @return array<string, array{database: bool, email: bool, broadcast: bool}>
     */
    public function getPreferencesForUser(User $user): array
    {
        /** @var Collection<int, UserNotificationPreference> $existing */
        $existing = UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->get();

        $result = [];
        foreach (NotificationEventCategory::cases() as $category) {
            /** @var UserNotificationPreference|null $pref */
            $pref = $existing->firstWhere('event_category', $category);

            $result[$category->value] = [
                'database' => $pref !== null ? $pref->channel_database : true,
                'email' => $pref !== null ? $pref->channel_email : true,
                'broadcast' => $pref !== null ? $pref->channel_broadcast : true,
            ];
        }

        return $result;
    }

    /**
     * Update user preferences from form array data.
     *
     * @param  array<string, array{database?: bool, email?: bool, broadcast?: bool}>  $preferencesData
     */
    public function updatePreferencesForUser(User $user, array $preferencesData): void
    {
        DB::transaction(function () use ($user, $preferencesData): void {
            foreach (NotificationEventCategory::cases() as $category) {
                $catData = $preferencesData[$category->value] ?? [];

                $dbChannel = (bool) ($catData['database'] ?? true);
                $emailChannel = (bool) ($catData['email'] ?? true);
                $broadcastChannel = (bool) ($catData['broadcast'] ?? true);

                UserNotificationPreference::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'event_category' => $category->value,
                    ],
                    [
                        'channel_database' => $dbChannel,
                        'channel_email' => $emailChannel,
                        'channel_broadcast' => $broadcastChannel,
                    ]
                );
            }
        });
    }

    /**
     * Reset user preferences back to default (all channels enabled).
     */
    public function resetToDefault(User $user): void
    {
        UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Check if a specific notification channel should be sent for a given event category.
     */
    public function shouldSend(User $user, NotificationEventCategory|string $eventCategory, string $channel): bool
    {
        $catValue = $eventCategory instanceof NotificationEventCategory ? $eventCategory->value : $eventCategory;
        $normalizedChannel = match (strtolower($channel)) {
            'mail', 'email' => 'channel_email',
            'broadcast', 'push', 'web' => 'channel_broadcast',
            default => 'channel_database',
        };

        /** @var UserNotificationPreference|null $pref */
        $pref = UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('event_category', $catValue)
            ->first();

        if ($pref === null) {
            return true; // Default enabled
        }

        return (bool) $pref->getAttribute($normalizedChannel);
    }
}
