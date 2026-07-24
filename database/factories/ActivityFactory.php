<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
final class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_type' => fake()->randomElement(ActivityType::cases()),
            'subject_type' => Opportunity::class,
            'subject_id' => 1,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'user_id' => User::factory(),
            'performed_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'duration_minutes' => fake()->randomElement([15, 30, 45, 60]),
            'location' => fake()->randomElement(['Zoom Meeting', 'Văn phòng công ty', 'Google Meet', 'Trực tiếp tại trụ sở khách hàng']),
        ];
    }
}
