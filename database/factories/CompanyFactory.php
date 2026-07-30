<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
final class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'tax_code' => fake()->unique()->numerify('03#########'),
            'website' => fake()->url(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('028########'),
            'industry' => fake()->randomElement(['Công nghệ thông tin', 'Tài chính - Ngân hàng', 'Bất động sản', 'Sản xuất', 'Bán lẻ']),
            'company_size' => fake()->randomElement(['1-10 nhân sự', '11-50 nhân sự', '51-200 nhân sự', '201-500 nhân sự', '500+ nhân sự']),
            'annual_revenue' => fake()->randomFloat(2, 50_000_000, 5_000_000_000),
            'address' => fake()->streetAddress(),
            'city' => 'TP. Hồ Chí Minh',
            'province' => 'TP. Hồ Chí Minh',
            'country' => 'Việt Nam',
            'notes' => fake()->sentence(),
            'owner_id' => User::factory(),
            'department_id' => Department::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function ownedBy(User $user): static
    {
        return $this->state(fn (): array => [
            'owner_id' => $user->getKey(),
            'department_id' => $user->department_id,
            'created_by' => $user->getKey(),
            'updated_by' => $user->getKey(),
        ]);
    }
}
