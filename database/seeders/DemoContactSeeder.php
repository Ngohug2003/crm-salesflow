<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

final class DemoContactSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->orderBy('id')->get();
        if ($companies->isEmpty()) {
            return;
        }

        $baseDate = CarbonImmutable::parse('2026-07-22 09:00:00', 'Asia/Ho_Chi_Minh');

        foreach ($this->demoContacts() as $index => $data) {
            $number = $index + 1;
            $company = $companies[$index % $companies->count()];
            $createdAt = $baseDate->subDays($index)->addHours($index % 5);

            $contact = Contact::query()->updateOrCreate(
                ['email' => sprintf('contact.demo%02d@salesflow.test', $number)],
                [
                    'company_id' => $company->getKey(),
                    'owner_id' => $company->owner_id,
                    'department_id' => $company->department_id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'full_name' => "{$data['last_name']} {$data['first_name']}",
                    'phone' => sprintf('0909%06d', $number),
                    'secondary_phone' => sprintf('0912%06d', $number),
                    'job_title' => $data['job_title'],
                    'department_name' => $data['department_name'],
                    'birthday' => '1990-05-15',
                    'is_primary' => $index % 2 === 0,
                    'address' => sprintf('%d Đường Lê Lợi', $number * 2),
                    'city' => 'TP. Hồ Chí Minh',
                    'province' => 'TP. Hồ Chí Minh',
                    'country' => 'Việt Nam',
                    'notes' => 'Người liên hệ mẫu đại diện doanh nghiệp khách hàng.',
                    'created_by' => $company->created_by,
                    'updated_by' => $company->updated_by,
                ],
            );

            $contact->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();
        }
    }

    /** @return list<array{first_name: string, last_name: string, job_title: string, department_name: string}> */
    private function demoContacts(): array
    {
        return [
            ['first_name' => 'Anh', 'last_name' => 'Nguyễn Văn', 'job_title' => 'Giám đốc Công nghệ', 'department_name' => 'Công nghệ thông tin'],
            ['first_name' => 'Bình', 'last_name' => 'Trần Thị', 'job_title' => 'Trưởng phòng Mua hàng', 'department_name' => 'Thu mua'],
            ['first_name' => 'Cường', 'last_name' => 'Lê Hoàng', 'job_title' => 'Giám đốc Kinh doanh', 'department_name' => 'Kinh doanh'],
            ['first_name' => 'Dung', 'last_name' => 'Phạm Phương', 'job_title' => 'Kế toán trưởng', 'department_name' => 'Tài chính Kế toán'],
            ['first_name' => 'Em', 'last_name' => 'Hoàng Minh', 'job_title' => 'Quản lý Dự án', 'department_name' => 'Vận hành'],
            ['first_name' => 'Giang', 'last_name' => 'Vũ Hương', 'job_title' => 'Trưởng phòng Nhân sự', 'department_name' => 'Nhân sự'],
            ['first_name' => 'Hải', 'last_name' => 'Đặng Quốc', 'job_title' => 'Chủ tịch HĐQT', 'department_name' => 'Ban Giám đốc'],
            ['first_name' => 'Khánh', 'last_name' => 'Bùi Tuấn', 'job_title' => 'Giám đốc Marketing', 'department_name' => 'Marketing'],
            ['first_name' => 'Linh', 'last_name' => 'Ngô Mỹ', 'job_title' => 'Chuyên viên Mua hàng', 'department_name' => 'Thu mua'],
            ['first_name' => 'Minh', 'last_name' => 'Đỗ Nhật', 'job_title' => 'Trưởng phòng IT', 'department_name' => 'Công nghệ thông tin'],
            ['first_name' => 'Nam', 'last_name' => 'Dương Hoài', 'job_title' => 'Phó Giám đốc Kinh doanh', 'department_name' => 'Kinh doanh'],
            ['first_name' => 'Oanh', 'last_name' => 'Lý Kim', 'job_title' => 'Trưởng phòng Pháp chế', 'department_name' => 'Pháp chế'],
            ['first_name' => 'Phong', 'last_name' => 'Hồ Thanh', 'job_title' => 'Quản lý Vận hành', 'department_name' => 'Vận hành'],
            ['first_name' => 'Quyên', 'last_name' => 'Phan Thảo', 'job_title' => 'Chuyên viên Nhân sự', 'department_name' => 'Nhân sự'],
            ['first_name' => 'Sơn', 'last_name' => 'Mai Thái', 'job_title' => 'Giám đốc Vận hành', 'department_name' => 'Ban Giám đốc'],
        ];
    }
}
