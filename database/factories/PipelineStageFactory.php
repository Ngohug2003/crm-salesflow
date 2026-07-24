<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PipelineStage>
 */
final class PipelineStageFactory extends Factory
{
    protected $model = PipelineStage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->word().' Stage';

        return [
            'pipeline_id' => Pipeline::factory(),
            'name' => ucfirst($name),
            'code' => Str::slug($name).'-'.fake()->unique()->randomNumber(3),
            'description' => fake()->sentence(),
            'position' => fake()->numberBetween(1, 10),
            'probability' => fake()->numberBetween(10, 90),
            'color' => fake()->hexColor(),
            'is_won' => false,
            'is_lost' => false,
            'is_system' => false,
        ];
    }
}
