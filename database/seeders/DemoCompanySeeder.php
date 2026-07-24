<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

final class DemoCompanySeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, User> $owners */
        $owners = User::query()->whereIn('email', $this->ownerEmails())->get()->keyBy('email')->all();
        $baseDate = CarbonImmutable::parse('2026-07-22 09:00:00', 'Asia/Ho_Chi_Minh');

        foreach ($this->demoCompanies() as $index => $data) {
            $number = $index + 1;
            $ownerEmail = $this->ownerEmailFor($index);
            $owner = $owners[$ownerEmail] ?? User::query()->where('email', 'admin@salesflow.test')->first();
            $createdAt = $baseDate->subDays($index)->addHours($index % 6);

            $company = Company::query()->updateOrCreate(
                ['tax_code' => $data['tax_code']],
                [
                    'owner_id' => $owner?->getKey(),
                    'department_id' => $owner?->department_id,
                    'name' => $data['name'],
                    'website' => $data['website'],
                    'email' => sprintf('contact%02d@%s', $number, parse_url($data['website'], PHP_URL_HOST) ?? 'company.test'),
                    'phone' => sprintf('02838%05d', $number),
                    'industry' => $data['industry'],
                    'company_size' => $data['company_size'],
                    'annual_revenue' => $data['annual_revenue'],
                    'address' => sprintf('%d Đường Nguyễn Huệ, Phường Bến Nghé, Quận 1', $number * 5),
                    'city' => 'TP. Hồ Chí Minh',
                    'province' => 'TP. Hồ Chí Minh',
                    'country' => 'Việt Nam',
                    'notes' => 'Doanh nghiệp khách hàng mẫu phục vụ trải nghiệm và kiểm thử.',
                    'created_by' => $owner?->getKey(),
                    'updated_by' => $owner?->getKey(),
                ],
            );

            $company->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();
        }
    }

    /** @return list<array{name: string, tax_code: string, website: string, industry: string, company_size: string, annual_revenue: float}> */
    private function demoCompanies(): array
    {
        return [
            ['name' => 'Công ty Cổ phần Công nghệ Ánh Dương', 'tax_code' => '0312345601', 'website' => 'https://anhduongtech.test', 'industry' => 'Công nghệ thông tin', 'company_size' => '51-200 nhân sự', 'annual_revenue' => 1500000000.0],
            ['name' => 'Tập đoàn Tài chính Sao Việt', 'tax_code' => '0312345602', 'website' => 'https://saovietgroup.test', 'industry' => 'Tài chính - Ngân hàng', 'company_size' => '201-500 nhân sự', 'annual_revenue' => 5500000000.0],
            ['name' => 'Công ty TNHH Bất động sản Minh Long', 'tax_code' => '0312345603', 'website' => 'https://minhlongland.test', 'industry' => 'Bất động sản', 'company_size' => '11-50 nhân sự', 'annual_revenue' => 800000000.0],
            ['name' => 'Công ty Sản xuất & Thương mại Đại Phát', 'tax_code' => '0312345604', 'website' => 'https://daiphatcorp.test', 'industry' => 'Sản xuất', 'company_size' => '500+ nhân sự', 'annual_revenue' => 12000000000.0],
            ['name' => 'Chuỗi Bán lẻ Đông Nam', 'tax_code' => '0312345605', 'website' => 'https://dongnamretail.test', 'industry' => 'Bán lẻ', 'company_size' => '51-200 nhân sự', 'annual_revenue' => 3200000000.0],
            ['name' => 'Công ty Giải pháp Y tế Hưng Thịnh', 'tax_code' => '0312345606', 'website' => 'https://hungthinhmed.test', 'industry' => 'Y tế - Dược phẩm', 'company_size' => '11-50 nhân sự', 'annual_revenue' => 650000000.0],
            ['name' => 'Tập đoàn Giáo dục Quốc tế Việt Nam', 'tax_code' => '0312345607', 'website' => 'https://viedu.test', 'industry' => 'Giáo dục', 'company_size' => '201-500 nhân sự', 'annual_revenue' => 4100000000.0],
            ['name' => 'Công ty Cổ phần Phần mềm Toàn Cầu', 'tax_code' => '0312345608', 'website' => 'https://globalsoft.test', 'industry' => 'Công nghệ thông tin', 'company_size' => '1-10 nhân sự', 'annual_revenue' => 350000000.0],
            ['name' => 'Công ty TNHH Logistics Nam Việt', 'tax_code' => '0312345609', 'website' => 'https://namvietlog.test', 'industry' => 'Sản xuất', 'company_size' => '51-200 nhân sự', 'annual_revenue' => 2100000000.0],
            ['name' => 'Công ty Tư vấn Tài chính An Bình', 'tax_code' => '0312345610', 'website' => 'https://anbinhfin.test', 'industry' => 'Tài chính - Ngân hàng', 'company_size' => '11-50 nhân sự', 'annual_revenue' => 950000000.0],
            ['name' => 'Công ty Cổ phần Đầu tư Xây dựng Bảo An', 'tax_code' => '0312345611', 'website' => 'https://baoancon.test', 'industry' => 'Bất động sản', 'company_size' => '201-500 nhân sự', 'annual_revenue' => 6800000000.0],
            ['name' => 'Công ty TNHH Dược phẩm Thái Bình Dương', 'tax_code' => '0312345612', 'website' => 'https://pacificpharma.test', 'industry' => 'Y tế - Dược phẩm', 'company_size' => '51-200 nhân sự', 'annual_revenue' => 2900000000.0],
        ];
    }

    /** @return list<string> */
    private function ownerEmails(): array
    {
        return [
            'demo03@salesflow.test',
            'demo04@salesflow.test',
            'demo05@salesflow.test',
            'demo06@salesflow.test',
            'demo09@salesflow.test',
            'demo10@salesflow.test',
        ];
    }

    private function ownerEmailFor(int $index): string
    {
        $emails = $this->ownerEmails();

        return $emails[$index % count($emails)];
    }
}
