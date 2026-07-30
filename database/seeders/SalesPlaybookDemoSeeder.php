<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PlaybookRepeatPolicy;
use App\Enums\SalesPlaybookStatus;
use App\Enums\SalesPlaybookStepType;
use App\Models\Pipeline;
use App\Models\SalesPlaybook;
use App\Models\StagePlaybookAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

final class SalesPlaybookDemoSeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::query()->where('email', 'admin@salesflow.test')->first();
        $pipeline = Pipeline::query()->default()->with('stages')->first();
        if ($actor === null || $pipeline === null) {
            return;
        }

        $stage = $pipeline->stages
            ->first(fn ($item): bool => ! $item->is_won && ! $item->is_lost);
        if ($stage === null) {
            return;
        }

        $playbook = SalesPlaybook::query()->firstOrCreate(
            ['code' => 'qualification-b2b', 'version' => 1],
            [
                'name' => 'Qualification cơ hội B2B',
                'description' => 'Xác định nhu cầu, ngân sách và người ra quyết định trước khi chuyển sang bước tiếp theo.',
                'status' => SalesPlaybookStatus::Published,
                'repeat_policy' => PlaybookRepeatPolicy::Once,
                'created_by' => $actor->id,
                'published_by' => $actor->id,
                'published_at' => now(),
            ],
        );

        if ($playbook->steps()->doesntExist()) {
            $playbook->steps()->createMany([
                [
                    'position' => 1,
                    'type' => SalesPlaybookStepType::Guidance,
                    'title' => 'Xác định mục tiêu kinh doanh của khách hàng',
                    'instructions' => 'Ghi nhận vấn đề chính và kết quả khách hàng mong muốn.',
                    'is_required' => false,
                    'blocks_stage_exit' => false,
                ],
                [
                    'position' => 2,
                    'type' => SalesPlaybookStepType::RequiredField,
                    'title' => 'Cập nhật giá trị cơ hội',
                    'instructions' => 'Nhập giá trị dự kiến trước khi rời giai đoạn.',
                    'configuration' => ['field_name' => 'amount'],
                    'is_required' => true,
                    'blocks_stage_exit' => true,
                ],
                [
                    'position' => 3,
                    'type' => SalesPlaybookStepType::Checklist,
                    'title' => 'Xác nhận người ra quyết định',
                    'instructions' => 'Xác định người có quyền phê duyệt ngân sách.',
                    'is_required' => true,
                    'blocks_stage_exit' => true,
                ],
                [
                    'position' => 4,
                    'type' => SalesPlaybookStepType::Task,
                    'title' => 'Liên hệ qualification lần đầu',
                    'instructions' => 'Trao đổi nhu cầu, ngân sách, thẩm quyền và thời gian dự kiến.',
                    'is_required' => true,
                    'blocks_stage_exit' => false,
                    'due_business_days' => 2,
                ],
            ]);
        }

        StagePlaybookAssignment::query()->firstOrCreate(
            ['pipeline_stage_id' => $stage->id],
            [
                'sales_playbook_id' => $playbook->id,
                'assigned_by' => $actor->id,
                'is_active' => true,
            ],
        );
    }
}
