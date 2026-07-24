<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Opportunity>
 */
final class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = 'Cơ hội Bán hàng '.fake()->company();
        $pipeline = Pipeline::query()->first() ?? Pipeline::factory()->create();
        $stage = PipelineStage::query()->where('pipeline_id', $pipeline->id)->first()
            ?? PipelineStage::factory()->create(['pipeline_id' => $pipeline->id]);

        return [
            'title' => $title,
            'code' => 'OPP-'.strtoupper(Str::random(6)),
            'amount' => fake()->randomFloat(2, 5000000, 500000000),
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'company_id' => Company::factory(),
            'contact_id' => Contact::factory(),
            'lead_id' => null,
            'owner_id' => null,
            'department_id' => null,
            'created_by' => null,
            'updated_by' => null,
            'expected_close_date' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
            'actual_close_date' => null,
            'lost_reason' => null,
            'notes' => fake()->sentence(),
            'is_won' => false,
            'is_lost' => false,
        ];
    }
}
