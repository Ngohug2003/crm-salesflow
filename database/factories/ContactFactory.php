<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
final class ContactFactory extends Factory
{
    protected $model = Contact::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'company_id' => Company::factory(),
            'owner_id' => User::factory(),
            'department_id' => Department::factory(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => "{$lastName} {$firstName}",
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('09########'),
            'secondary_phone' => fake()->numerify('09########'),
            'job_title' => fake()->randomElement(['Trưởng phòng Kế toán', 'Giám đốc Kinh doanh', 'Chuyên viên Tư vấn', 'Trưởng nhóm IT']),
            'department_name' => fake()->randomElement(['Kinh doanh', 'Công nghệ thông tin', 'Kế toán', 'Nhân sự']),
            'birthday' => fake()->date(),
            'is_primary' => false,
            'address' => fake()->streetAddress(),
            'city' => 'TP. Hồ Chí Minh',
            'province' => 'TP. Hồ Chí Minh',
            'country' => 'Việt Nam',
            'notes' => fake()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (): array => [
            'is_primary' => true,
        ]);
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

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->getKey(),
            'owner_id' => $company->owner_id,
            'department_id' => $company->department_id,
            'created_by' => $company->created_by,
            'updated_by' => $company->updated_by,
        ]);
    }
}
