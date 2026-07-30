<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DemoActivitySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first() ?? User::factory()->create();

        $leads = Lead::query()->count() > 0 ? Lead::query()->take(2)->get() : Lead::factory()->count(2)->create();
        foreach ($leads as $lead) {
            Activity::query()->create([
                'activity_type' => ActivityType::Call,
                'subject_type' => Lead::class,
                'subject_id' => $lead->id,
                'title' => "Cuộc gọi tư vấn giải pháp cho Lead {$lead->full_name}",
                'description' => 'Khách hàng quan tâm đến tính năng tự động hóa và quản lý phễu bán hàng.',
                'user_id' => $user->id,
                'performed_at' => now()->subDays(2),
                'duration_minutes' => 20,
                'location' => 'Điện thoại',
            ]);
        }

        $companies = Company::query()->count() > 0 ? Company::query()->take(2)->get() : Company::factory()->count(2)->create();
        foreach ($companies as $company) {
            Activity::query()->create([
                'activity_type' => ActivityType::Meeting,
                'subject_type' => Company::class,
                'subject_id' => $company->id,
                'title' => "Họp trao đổi yêu cầu hợp đồng với {$company->name}",
                'description' => 'Thống nhất điều khoản thanh toán và lộ trình triển khai 3 tháng.',
                'user_id' => $user->id,
                'performed_at' => now()->subDay(),
                'duration_minutes' => 60,
                'location' => 'Văn phòng Công ty',
            ]);
        }

        $contacts = Contact::query()->count() > 0 ? Contact::query()->take(2)->get() : Contact::factory()->count(2)->create();
        foreach ($contacts as $contact) {
            Activity::query()->create([
                'activity_type' => ActivityType::Email,
                'subject_type' => Contact::class,
                'subject_id' => $contact->id,
                'title' => "Gửi hồ sơ năng lực và đề xuất báo giá cho {$contact->full_name}",
                'description' => 'Đã gửi file PDF báo giá chi tiết qua email cá nhân.',
                'user_id' => $user->id,
                'performed_at' => now()->subHours(5),
                'duration_minutes' => 15,
                'location' => 'Email',
            ]);
        }

        $opportunities = Opportunity::query()->count() > 0 ? Opportunity::query()->take(3)->get() : Opportunity::factory()->count(3)->create();
        foreach ($opportunities as $opp) {
            Activity::query()->create([
                'activity_type' => ActivityType::Demo,
                'subject_type' => Opportunity::class,
                'subject_id' => $opp->id,
                'title' => "Demo trực tiếp giải pháp cho dự án '{$opp->title}'",
                'description' => 'Trình diễn tính năng Kanban view và báo cáo dự báo doanh số.',
                'user_id' => $user->id,
                'performed_at' => now(),
                'duration_minutes' => 45,
                'location' => 'Google Meet',
            ]);
        }
    }
}
