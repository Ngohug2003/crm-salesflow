<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LeadSource;
use App\Models\Tag;
use Illuminate\Database\Seeder;

final class LeadTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->sources() as $source) {
            LeadSource::query()->updateOrCreate(
                ['code' => $source['code']],
                $source,
            );
        }

        foreach ($this->tags() as $tag) {
            Tag::query()->updateOrCreate(
                ['slug' => $tag['slug']],
                $tag,
            );
        }
    }

    /** @return list<array{name: string, code: string, description: string, color: string, is_active: bool, sort_order: int}> */
    private function sources(): array
    {
        return [
            ['name' => 'Website', 'code' => 'WEBSITE', 'description' => 'Khách hàng gửi thông tin qua website.', 'color' => '#2563EB', 'is_active' => true, 'sort_order' => 10],
            ['name' => 'Facebook', 'code' => 'FACEBOOK', 'description' => 'Khách hàng đến từ Facebook hoặc quảng cáo Meta.', 'color' => '#1877F2', 'is_active' => true, 'sort_order' => 20],
            ['name' => 'Zalo', 'code' => 'ZALO', 'description' => 'Khách hàng liên hệ qua Zalo.', 'color' => '#0068FF', 'is_active' => true, 'sort_order' => 30],
            ['name' => 'Giới thiệu', 'code' => 'REFERRAL', 'description' => 'Khách hàng được người quen hoặc khách hàng cũ giới thiệu.', 'color' => '#7C3AED', 'is_active' => true, 'sort_order' => 40],
            ['name' => 'Hotline', 'code' => 'HOTLINE', 'description' => 'Khách hàng gọi trực tiếp đến hotline.', 'color' => '#DC2626', 'is_active' => true, 'sort_order' => 50],
            ['name' => 'Sự kiện', 'code' => 'EVENT', 'description' => 'Khách hàng thu thập từ hội thảo hoặc sự kiện.', 'color' => '#EA580C', 'is_active' => true, 'sort_order' => 60],
            ['name' => 'Đối tác', 'code' => 'PARTNER', 'description' => 'Khách hàng được chuyển đến từ đối tác.', 'color' => '#059669', 'is_active' => true, 'sort_order' => 70],
            ['name' => 'Nhập thủ công', 'code' => 'MANUAL', 'description' => 'Khách hàng được nhân viên nhập trực tiếp.', 'color' => '#64748B', 'is_active' => true, 'sort_order' => 80],
        ];
    }

    /** @return list<array{name: string, slug: string, description: string, color: string, is_active: bool, sort_order: int}> */
    private function tags(): array
    {
        return [
            ['name' => 'Mới', 'slug' => 'moi', 'description' => 'Lead mới được tiếp nhận.', 'color' => '#0EA5E9', 'is_active' => true, 'sort_order' => 10],
            ['name' => 'Tiềm năng cao', 'slug' => 'tiem-nang-cao', 'description' => 'Lead có khả năng chuyển đổi cao.', 'color' => '#EF4444', 'is_active' => true, 'sort_order' => 20],
            ['name' => 'VIP', 'slug' => 'vip', 'description' => 'Lead có giá trị hoặc mức độ ưu tiên đặc biệt.', 'color' => '#A855F7', 'is_active' => true, 'sort_order' => 30],
            ['name' => 'Cần chăm sóc', 'slug' => 'can-cham-soc', 'description' => 'Lead cần được liên hệ hoặc theo dõi tiếp.', 'color' => '#F59E0B', 'is_active' => true, 'sort_order' => 40],
            ['name' => 'Đã xác thực', 'slug' => 'da-xac-thuc', 'description' => 'Thông tin liên hệ của Lead đã được xác thực.', 'color' => '#10B981', 'is_active' => true, 'sort_order' => 50],
            ['name' => 'Khách hàng cũ', 'slug' => 'khach-hang-cu', 'description' => 'Lead từng phát sinh giao dịch với doanh nghiệp.', 'color' => '#6366F1', 'is_active' => true, 'sort_order' => 60],
        ];
    }
}
