<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Pipeline;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pipeline>
 */
final class PipelineFactory extends Factory
{
    protected $model = Pipeline::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Pipeline';

        return [
            'name' => $name,
            'code' => Str::slug($name).'-'.fake()->unique()->randomNumber(3),
            'description' => fake()->sentence(),
            'is_default' => false,
            'is_active' => true,
            'owner_id' => null,
            'department_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
