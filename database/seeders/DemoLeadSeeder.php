<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Tag;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

final class DemoLeadSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, LeadSource> $sources */
        $sources = LeadSource::query()->ordered()->get()->keyBy('code')->all();
        /** @var array<string, Tag> $tags */
        $tags = Tag::query()->ordered()->get()->keyBy('slug')->all();
        /** @var array<string, User> $owners */
        $owners = User::query()->whereIn('email', $this->ownerEmails())->get()->keyBy('email')->all();
        $baseDate = CarbonImmutable::parse('2026-07-22 09:00:00', 'Asia/Ho_Chi_Minh');
        $statuses = LeadStatus::cases();
        $priorities = LeadPriority::cases();
        $sourceCodes = array_keys($sources);
        $tagSlugs = array_keys($tags);

        foreach ($this->names() as $index => $name) {
            $number = $index + 1;
            $ownerEmail = $this->ownerEmailFor($index);
            $owner = $owners[$ownerEmail];
            $status = $statuses[$index % count($statuses)];
            $createdAt = $baseDate->subDays($index)->addHours($index % 8);

            $lead = Lead::query()->updateOrCreate(
                ['email' => sprintf('lead.demo%02d@salesflow.test', $number)],
                [
                    'lead_source_id' => $sources[$sourceCodes[$index % count($sourceCodes)]]->getKey(),
                    'owner_id' => $owner->getKey(),
                    'department_id' => $owner->department_id,
                    'full_name' => $name,
                    'phone' => sprintf('0908%06d', $number),
                    'secondary_phone' => null,
                    'company_name' => $this->companies()[$index % count($this->companies())],
                    'job_title' => $this->jobTitles()[$index % count($this->jobTitles())],
                    'website' => null,
                    'address' => sprintf('%d Đường Nguyễn Huệ', $number),
                    'city' => $index % 3 === 0 ? 'Hà Nội' : 'TP. Hồ Chí Minh',
                    'province' => $index % 3 === 0 ? 'Hà Nội' : 'TP. Hồ Chí Minh',
                    'country' => 'Việt Nam',
                    'status' => $status,
                    'priority' => $priorities[$index % count($priorities)],
                    'estimated_value' => (string) (25_000_000 + ($index * 7_500_000)),
                    'notes' => 'Dữ liệu Lead demo phục vụ phát triển và kiểm thử.',
                    'converted_at' => $status === LeadStatus::Converted ? $createdAt->addDays(5) : null,
                    'created_by' => $owner->getKey(),
                    'updated_by' => $owner->getKey(),
                ],
            );

            $lead->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();

            $lead->tags()->sync([
                $tags[$tagSlugs[$index % count($tagSlugs)]]->getKey(),
                $tags[$tagSlugs[($index + 2) % count($tagSlugs)]]->getKey(),
            ]);
        }
    }

    /** @return list<string> */
    private function names(): array
    {
        return [
            'Nguyễn Hoàng Anh', 'Trần Minh Châu', 'Lê Quốc Đạt', 'Phạm Thu Giang', 'Hoàng Gia Hân',
            'Đỗ Minh Khang', 'Vũ Ngọc Lan', 'Bùi Thành Long', 'Ngô Quỳnh Mai', 'Đặng Đức Nam',
            'Dương Khánh Ngân', 'Lý Tuấn Phong', 'Hồ Bảo Quân', 'Phan Thùy Trang', 'Mai Anh Tú',
            'Tạ Hải Yến', 'Cao Nhật Minh', 'Đinh Thanh Hà', 'Trịnh Quốc Huy', 'Võ Phương Linh',
            'Nguyễn Công Thành', 'Trần Mỹ Duyên', 'Lê Trung Kiên', 'Phạm Bích Ngọc', 'Hoàng Tuấn Vũ',
            'Đỗ Kim Oanh', 'Vũ Đức Thắng', 'Bùi Hà My', 'Ngô Quang Vinh', 'Đặng Thu Thủy',
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
            'demo11@salesflow.test',
            'demo14@salesflow.test',
            'demo15@salesflow.test',
            'demo16@salesflow.test',
            'demo17@salesflow.test',
        ];
    }

    private function ownerEmailFor(int $index): string
    {
        $salesOwners = array_slice($this->ownerEmails(), 0, 7);
        $marketingOwners = array_slice($this->ownerEmails(), 7);
        $pool = $index < 18 ? $salesOwners : $marketingOwners;

        return $pool[$index % count($pool)];
    }

    /** @return list<string> */
    private function companies(): array
    {
        return [
            'Công ty Ánh Dương',
            'Công ty Sao Việt',
            'Công ty Minh Long',
            'Công ty Đại Phát',
            'Công ty Đông Nam',
            'Công ty Hưng Thịnh',
        ];
    }

    /** @return list<string> */
    private function jobTitles(): array
    {
        return [
            'Giám đốc',
            'Trưởng phòng kinh doanh',
            'Quản lý mua hàng',
            'Chuyên viên marketing',
            'Chủ doanh nghiệp',
        ];
    }
}
