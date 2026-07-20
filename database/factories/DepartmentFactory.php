<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Department';

        return [
            'parent_id' => null,
            'name' => $name,
            'code' => strtoupper(fake()->unique()->bothify('DEP-####')),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function childOf(Department $department): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $department->getKey(),
        ]);
    }
}
