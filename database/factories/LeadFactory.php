<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
final class LeadFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'lead_source_id' => LeadSource::factory(),
            'owner_id' => null,
            'department_id' => Department::factory(),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('09########'),
            'secondary_phone' => fake()->optional()->numerify('02########'),
            'company_name' => fake()->optional()->company(),
            'job_title' => fake()->optional()->jobTitle(),
            'website' => fake()->optional()->url(),
            'address' => fake()->optional()->streetAddress(),
            'city' => fake()->optional()->city(),
            'province' => fake()->optional()->city(),
            'country' => 'Việt Nam',
            'status' => LeadStatus::New,
            'priority' => fake()->randomElement(LeadPriority::cases()),
            'estimated_value' => fake()->optional()->randomFloat(2, 1_000_000, 1_000_000_000),
            'notes' => fake()->optional()->paragraph(),
            'converted_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function ownedBy(User $owner): static
    {
        return $this->state(fn (array $attributes): array => [
            'owner_id' => $owner->getKey(),
            'department_id' => $owner->department_id,
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_by' => $user->getKey(),
            'updated_by' => $user->getKey(),
        ]);
    }

    public function withStatus(LeadStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }

    public function withPriority(LeadPriority $priority): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => $priority,
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LeadStatus::Converted,
            'converted_at' => now(),
        ]);
    }
}
