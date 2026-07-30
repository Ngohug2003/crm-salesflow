<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PlaybookRepeatPolicy;
use App\Enums\SalesPlaybookStatus;
use App\Enums\SalesPlaybookStepType;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\SalesPlaybook;
use App\Models\StagePlaybookAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class StandardSalesPlaybookSeeder extends Seeder
{
    private const PIPELINE_CODE = 'standard-sales-pipeline';

    public function run(): void
    {
        DB::transaction(function (): void {
            $actor = User::query()->where('email', 'admin@salesflow.test')->first();
            if ($actor === null) {
                throw new RuntimeException('Không thể seed Standard Sales Playbook: thiếu admin@salesflow.test.');
            }

            $pipeline = Pipeline::query()
                ->where('code', self::PIPELINE_CODE)
                ->with('stages')
                ->first();
            if ($pipeline === null) {
                throw new RuntimeException('Không thể seed Standard Sales Playbook: thiếu Quy trình Bán hàng Standard.');
            }

            $definitions = $this->definitions();
            $stages = $pipeline->stages->keyBy('code');
            $missingStages = array_values(array_diff(array_keys($definitions), $stages->keys()->all()));
            if ($missingStages !== []) {
                throw new RuntimeException(
                    'Không thể seed Standard Sales Playbook: thiếu stage '.implode(', ', $missingStages).'.',
                );
            }

            foreach ($definitions as $stageCode => $definition) {
                /** @var PipelineStage $stage */
                $stage = $stages->get($stageCode);
                $playbook = $this->upsertPlaybook($actor, $stage, $definition);
                $this->assignToStage($actor, $stage, $playbook);
            }
        });
    }

    /**
     * @param  array{
     *     code: string,
     *     name: string,
     *     description: string,
     *     repeat_policy: PlaybookRepeatPolicy,
     *     steps: list<array<string, mixed>>
     * }  $definition
     */
    private function upsertPlaybook(User $actor, PipelineStage $stage, array $definition): SalesPlaybook
    {
        $playbook = SalesPlaybook::withTrashed()->firstOrNew([
            'code' => $definition['code'],
            'version' => 1,
        ]);

        if ($playbook->trashed()) {
            $playbook->restore();
        }

        $playbook->fill([
            'name' => $definition['name'],
            'description' => $definition['description'],
            'status' => SalesPlaybookStatus::Published,
            'repeat_policy' => $definition['repeat_policy'],
            'draft_pipeline_stage_id' => $stage->id,
            'created_by' => $actor->id,
            'published_by' => $actor->id,
            'published_at' => $playbook->published_at ?? now(),
        ])->save();

        $playbook->steps()->delete();
        $playbook->steps()->createMany($definition['steps']);

        return $playbook;
    }

    private function assignToStage(
        User $actor,
        PipelineStage $stage,
        SalesPlaybook $playbook,
    ): void {
        StagePlaybookAssignment::query()->updateOrCreate(
            ['pipeline_stage_id' => $stage->id],
            [
                'sales_playbook_id' => $playbook->id,
                'assigned_by' => $actor->id,
                'is_active' => true,
            ],
        );
    }

    /**
     * @return array<string, array{
     *     code: string,
     *     name: string,
     *     description: string,
     *     repeat_policy: PlaybookRepeatPolicy,
     *     steps: list<array<string, mixed>>
     * }>
     */
    private function definitions(): array
    {
        return [
            'initial-contact' => [
                'code' => 'standard-initial-contact',
                'name' => '01 — Tiếp cận và xác nhận liên hệ',
                'description' => 'Xác minh đầu mối, ghi nhận phản hồi ban đầu và thống nhất bước trao đổi tiếp theo.',
                'repeat_policy' => PlaybookRepeatPolicy::Once,
                'steps' => [
                    $this->step(1, SalesPlaybookStepType::Guidance, 'Mục tiêu giai đoạn', 'Xác nhận đúng khách hàng, đúng người liên hệ và lý do họ quan tâm.'),
                    $this->requiredField(2, 'Gán người phụ trách cơ hội', 'owner_id', 'Mỗi cơ hội phải có một Sales chịu trách nhiệm trước khi đi tiếp.'),
                    $this->step(3, SalesPlaybookStepType::Question, 'Kênh liên hệ và phản hồi ban đầu', 'Ghi rõ đã gọi/email/Zalo lúc nào và khách hàng phản hồi điều gì.', true, true),
                    $this->step(4, SalesPlaybookStepType::Checklist, 'Đã xác nhận cuộc trao đổi tiếp theo', 'Thống nhất ngày giờ hoặc hành động cụ thể tiếp theo với khách hàng.', true, true),
                    $this->step(5, SalesPlaybookStepType::Task, 'Theo dõi sau lần liên hệ đầu tiên', 'Liên hệ lại và cập nhật kết quả vào Opportunity.', true, false, 1),
                ],
            ],
            'needs-analysis' => [
                'code' => 'standard-needs-analysis',
                'name' => '02 — Phân tích nhu cầu B2B',
                'description' => 'Làm rõ bài toán, ngân sách, người quyết định và thời điểm dự kiến mua.',
                'repeat_policy' => PlaybookRepeatPolicy::Once,
                'steps' => [
                    $this->step(1, SalesPlaybookStepType::Guidance, 'Mục tiêu giai đoạn', 'Đủ dữ liệu để đánh giá cơ hội có phù hợp và đáng tiếp tục đầu tư hay không.'),
                    $this->step(2, SalesPlaybookStepType::Question, 'Vấn đề kinh doanh cần giải quyết', 'Mô tả hiện trạng, ảnh hưởng và kết quả khách hàng mong muốn.', true, true),
                    $this->step(3, SalesPlaybookStepType::Question, 'Người ra quyết định và quy trình duyệt', 'Ghi họ tên/chức vụ người duyệt, người ảnh hưởng và các bước phê duyệt nội bộ.', true, true),
                    $this->requiredField(4, 'Cập nhật giá trị cơ hội', 'amount', 'Nhập giá trị dự kiến dựa trên phạm vi nhu cầu đã xác nhận.'),
                    $this->requiredField(5, 'Cập nhật ngày dự kiến chốt', 'expected_close_date', 'Chọn ngày chốt có căn cứ từ kế hoạch mua của khách hàng.'),
                    $this->step(6, SalesPlaybookStepType::Checklist, 'Đã xác nhận mức độ phù hợp giải pháp', 'Đối chiếu nhu cầu với sản phẩm/dịch vụ doanh nghiệp có thể cung cấp.', true, true),
                    $this->step(7, SalesPlaybookStepType::Task, 'Hoàn thiện biên bản phân tích nhu cầu', 'Tóm tắt nhu cầu, ngân sách, người quyết định, thời gian và bước tiếp theo.', true, false, 2),
                ],
            ],
            'proposal-quote' => [
                'code' => 'standard-proposal-quote',
                'name' => '03 — Chuẩn bị và gửi đề xuất',
                'description' => 'Hoàn thiện hồ sơ khách hàng, phát hành báo giá đúng phạm vi và xác nhận khách hàng đã nhận.',
                'repeat_policy' => PlaybookRepeatPolicy::EveryEntry,
                'steps' => [
                    $this->step(1, SalesPlaybookStepType::Guidance, 'Mục tiêu giai đoạn', 'Gửi đề xuất phù hợp nhu cầu đã xác nhận và có lịch theo dõi rõ ràng.'),
                    $this->requiredField(2, 'Liên kết công ty khách hàng', 'company_id', 'Chọn đúng công ty để báo giá và lịch sử giao dịch không bị phân tán.'),
                    $this->requiredField(3, 'Liên kết người liên hệ chính', 'contact_id', 'Chọn đầu mối nhận và phản hồi báo giá.'),
                    $this->requiredField(4, 'Xác nhận giá trị đề xuất', 'amount', 'Giá trị Opportunity phải khớp với báo giá/đề xuất mới nhất.'),
                    $this->step(5, SalesPlaybookStepType::Checklist, 'Báo giá đã được kiểm tra và phát hành', 'Kiểm tra sản phẩm, số lượng, thuế, chiết khấu, điều khoản và phiên bản PDF.', true, true),
                    $this->step(6, SalesPlaybookStepType::Checklist, 'Khách hàng xác nhận đã nhận đề xuất', 'Ghi nhận qua email, điện thoại hoặc kênh trao đổi chính thức.', true, true),
                    $this->step(7, SalesPlaybookStepType::Reminder, 'Theo dõi phản hồi báo giá', 'Nhắc Sales liên hệ khách hàng sau khi gửi đề xuất.', true, false, 2),
                ],
            ],
            'negotiation' => [
                'code' => 'standard-negotiation',
                'name' => '04 — Thương lượng và chốt điều khoản',
                'description' => 'Xử lý phản đối, thống nhất điều khoản thương mại và xác lập hành động chốt.',
                'repeat_policy' => PlaybookRepeatPolicy::EveryEntry,
                'steps' => [
                    $this->step(1, SalesPlaybookStepType::Guidance, 'Mục tiêu giai đoạn', 'Chuyển các điểm chưa thống nhất thành điều khoản rõ ràng và cam kết chốt cụ thể.'),
                    $this->step(2, SalesPlaybookStepType::Question, 'Phản đối hoặc vướng mắc còn lại', 'Ghi từng vướng mắc về giá, phạm vi, pháp lý, triển khai hoặc thanh toán.', true, true),
                    $this->step(3, SalesPlaybookStepType::Question, 'Lộ trình phê duyệt cuối cùng', 'Ai cần duyệt, hồ sơ nào còn thiếu và hạn phản hồi cuối cùng là ngày nào?', true, true),
                    $this->requiredField(4, 'Cập nhật ngày dự kiến chốt', 'expected_close_date', 'Điều chỉnh ngày chốt theo cam kết mới nhất của hai bên.'),
                    $this->step(5, SalesPlaybookStepType::Checklist, 'Đã thống nhất điều khoản thương mại', 'Giá, chiết khấu, thanh toán, phạm vi và thời hạn hiệu lực đã được xác nhận.', true, true),
                    $this->step(6, SalesPlaybookStepType::Checklist, 'Đã thống nhất hành động chốt tiếp theo', 'Có người phụ trách, thời hạn và kết quả mong đợi cho bước ký/chốt.', true, true),
                    $this->step(7, SalesPlaybookStepType::Task, 'Theo dõi bước chốt hợp đồng', 'Bám sát người quyết định và cập nhật kết quả thương lượng.', true, false, 1),
                ],
            ],
            'closed-won' => [
                'code' => 'standard-closed-won',
                'name' => '05 — Bàn giao khách hàng thành công',
                'description' => 'Hoàn tất hồ sơ thắng, bàn giao nội bộ và khởi động chăm sóc/triển khai khách hàng.',
                'repeat_policy' => PlaybookRepeatPolicy::Once,
                'steps' => [
                    $this->step(1, SalesPlaybookStepType::Guidance, 'Mục tiêu sau khi Won', 'Đảm bảo cam kết bán hàng được bàn giao đầy đủ cho bộ phận thực thi và chăm sóc.'),
                    $this->step(2, SalesPlaybookStepType::Checklist, 'Đã lưu xác nhận chốt hoặc hợp đồng', 'Kiểm tra hồ sơ ký kết, đơn đặt hàng hoặc xác nhận mua chính thức.', true),
                    $this->step(3, SalesPlaybookStepType::Checklist, 'Đã bàn giao đầy đủ thông tin khách hàng', 'Bàn giao phạm vi, đầu mối, thời hạn, cam kết đặc biệt và rủi ro cần theo dõi.', true),
                    $this->step(4, SalesPlaybookStepType::Task, 'Bàn giao nội bộ sau khi thắng', 'Tổ chức bàn giao cho đội triển khai/chăm sóc khách hàng.', true, false, 1),
                    $this->step(5, SalesPlaybookStepType::Reminder, 'Theo dõi khởi động khách hàng', 'Kiểm tra khách hàng đã được liên hệ và có kế hoạch onboarding.', true, false, 2),
                ],
            ],
            'closed-lost' => [
                'code' => 'standard-closed-lost',
                'name' => '06 — Đóng hồ sơ và rút kinh nghiệm',
                'description' => 'Ghi nhận nguyên nhân thất bại, bài học và kế hoạch nuôi dưỡng lại khi phù hợp.',
                'repeat_policy' => PlaybookRepeatPolicy::Once,
                'steps' => [
                    $this->step(1, SalesPlaybookStepType::Guidance, 'Mục tiêu sau khi Lost', 'Đóng dữ liệu có nguyên nhân rõ ràng để Manager cải thiện chiến lược và dự báo.'),
                    $this->step(2, SalesPlaybookStepType::Question, 'Nguyên nhân thất bại và đối thủ', 'Nêu nguyên nhân chính, đối thủ được chọn và yếu tố quyết định của khách hàng.', true),
                    $this->step(3, SalesPlaybookStepType::Checklist, 'Đã cập nhật đầy đủ kết quả cuối', 'Kiểm tra ghi chú, giá trị, ngày đóng và thông tin phản hồi cuối cùng.', true),
                    $this->step(4, SalesPlaybookStepType::Task, 'Rút kinh nghiệm cơ hội thất bại', 'Tóm tắt điều làm tốt, điều cần cải thiện và hành động áp dụng cho cơ hội sau.', true, false, 1),
                    $this->step(5, SalesPlaybookStepType::Reminder, 'Đánh giá khả năng nuôi dưỡng lại', 'Liên hệ lại khi nhu cầu, ngân sách hoặc thời điểm mua có thể thay đổi.', false, false, 30),
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function requiredField(
        int $position,
        string $title,
        string $fieldName,
        string $instructions,
    ): array {
        return $this->step(
            $position,
            SalesPlaybookStepType::RequiredField,
            $title,
            $instructions,
            true,
            true,
            null,
            ['field_name' => $fieldName],
        );
    }

    /**
     * @param  array<string, mixed>|null  $configuration
     * @return array<string, mixed>
     */
    private function step(
        int $position,
        SalesPlaybookStepType $type,
        string $title,
        string $instructions,
        bool $isRequired = false,
        bool $blocksStageExit = false,
        ?int $dueBusinessDays = null,
        ?array $configuration = null,
    ): array {
        return [
            'position' => $position,
            'type' => $type,
            'title' => $title,
            'instructions' => $instructions,
            'configuration' => $configuration,
            'is_required' => $isRequired,
            'blocks_stage_exit' => $blocksStageExit,
            'due_business_days' => $dueBusinessDays,
        ];
    }
}
