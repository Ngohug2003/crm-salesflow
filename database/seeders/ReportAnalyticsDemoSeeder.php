<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Opportunity;
use App\Models\OpportunityItem;
use App\Models\OpportunityStageHistory;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\PriceBookEntry;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ReportAnalyticsDemoSeeder extends Seeder
{
    private const int SCENARIO_COUNT = 100;

    public function run(): void
    {
        $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
        $stages = $pipeline->stages()->orderBy('position')->get();
        $openStages = $stages->where('is_won', false)->where('is_lost', false)->values();
        $wonStage = $stages->firstWhere('is_won', true);
        $lostStage = $stages->firstWhere('is_lost', true);
        $users = User::query()
            ->where('is_active', true)
            ->whereNotNull('department_id')
            ->orderBy('id')
            ->get();
        $sources = LeadSource::query()->where('is_active', true)->orderBy('sort_order')->get();

        if ($users->isEmpty() || $sources->isEmpty() || $openStages->isEmpty() || $wonStage === null || $lostStage === null) {
            $this->command->warn('Không đủ user, nguồn Lead hoặc pipeline stage để tạo dữ liệu báo cáo.');

            return;
        }

        $companies = Company::query()->orderBy('id')->get();
        $contacts = Contact::query()->orderBy('id')->get();
        $variants = ProductVariant::query()
            ->with('product')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        DB::transaction(function () use (
            $pipeline,
            $stages,
            $openStages,
            $wonStage,
            $lostStage,
            $users,
            $sources,
            $companies,
            $contacts,
            $variants,
        ): void {
            foreach (range(1, self::SCENARIO_COUNT) as $index) {
                $this->seedScenario(
                    $index,
                    $pipeline,
                    $stages,
                    $openStages,
                    $wonStage,
                    $lostStage,
                    $users,
                    $sources,
                    $companies,
                    $contacts,
                    $variants,
                );
            }
        });

        $this->command->info('Đã tạo 100 kịch bản demo báo cáo trải trên 24 tháng.');
    }

    /**
     * @param  Collection<int, PipelineStage>  $stages
     * @param  Collection<int, PipelineStage>  $openStages
     * @param  Collection<int, User>  $users
     * @param  Collection<int, LeadSource>  $sources
     * @param  Collection<int, Company>  $companies
     * @param  Collection<int, Contact>  $contacts
     * @param  Collection<int, ProductVariant>  $variants
     */
    private function seedScenario(
        int $index,
        Pipeline $pipeline,
        Collection $stages,
        Collection $openStages,
        PipelineStage $wonStage,
        PipelineStage $lostStage,
        Collection $users,
        Collection $sources,
        Collection $companies,
        Collection $contacts,
        Collection $variants,
    ): void {
        $createdAt = $this->scenarioDate($index);
        $owner = $users[($index - 1) % $users->count()];
        $source = $sources[($index - 1) % $sources->count()];
        $leadStatus = LeadStatus::cases()[($index - 1) % count(LeadStatus::cases())];
        $leadPriority = LeadPriority::cases()[($index - 1) % count(LeadPriority::cases())];
        $suffix = str_pad((string) $index, 3, '0', STR_PAD_LEFT);

        $lead = Lead::query()->updateOrCreate(
            ['email' => "report.demo{$suffix}@salesflow.test"],
            [
                'lead_source_id' => $source->id,
                'owner_id' => $owner->id,
                'department_id' => $owner->department_id,
                'full_name' => "Khách hàng Báo cáo {$suffix}",
                'phone' => '0988'.str_pad((string) $index, 6, '0', STR_PAD_LEFT),
                'company_name' => "Doanh nghiệp Demo {$suffix}",
                'job_title' => 'Người phụ trách mua hàng',
                'country' => 'Việt Nam',
                'status' => $leadStatus,
                'priority' => $leadPriority,
                'estimated_value' => 20_000_000 + ($index * 3_750_000),
                'converted_at' => $leadStatus === LeadStatus::Converted
                    ? $createdAt->copy()->addDays(5)
                    : null,
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ],
        );
        $this->setTimestamps($lead, $createdAt);

        $outcome = $index % 10;
        $stage = match (true) {
            $outcome <= 1 => $wonStage,
            $outcome === 2 => $lostStage,
            default => $openStages[($index - 1) % $openStages->count()],
        };
        $isWon = (bool) $stage->is_won;
        $isLost = (bool) $stage->is_lost;
        $closeDate = ($isWon || $isLost)
            ? $createdAt->copy()->addDays(7 + ($index % 20))->min(now())
            : null;
        $expectedCloseDate = $isWon || $isLost
            ? $closeDate
            : $createdAt->copy()->addDays($index <= 7 ? 3 : 12 + ($index % 20));

        $opportunity = Opportunity::query()->updateOrCreate(
            ['code' => "RPT-DEMO-{$suffix}"],
            [
                'title' => "Dự án nội thất văn phòng {$suffix}",
                'amount' => 25_000_000 + ($index * 8_250_000),
                'pipeline_id' => $pipeline->id,
                'stage_id' => $stage->id,
                'company_id' => $companies->isEmpty() ? null : $companies[($index - 1) % $companies->count()]->id,
                'contact_id' => $contacts->isEmpty() ? null : $contacts[($index - 1) % $contacts->count()]->id,
                'lead_id' => $lead->id,
                'owner_id' => $owner->id,
                'department_id' => $owner->department_id,
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
                'expected_close_date' => $expectedCloseDate->toDateString(),
                'actual_close_date' => $closeDate?->toDateString(),
                'lost_reason' => $isLost ? $this->lossReason($index) : null,
                'notes' => 'Dự án nội thất demo phục vụ kiểm tra Dashboard và báo cáo theo thời gian.',
                'is_won' => $isWon,
                'is_lost' => $isLost,
            ],
        );
        $this->setTimestamps($opportunity, $createdAt);
        $this->seedOpportunityItem($index, $opportunity, $variants);
        $this->seedStageHistory($opportunity, $stage, $stages, $owner, $createdAt);
        $this->seedActivity($index, $suffix, $opportunity, $owner, $createdAt);
        $this->seedTask($index, $suffix, $opportunity, $owner, $createdAt);
    }

    /** @param Collection<int, ProductVariant> $variants */
    private function seedOpportunityItem(int $index, Opportunity $opportunity, Collection $variants): void
    {
        /** @var ProductVariant $variant */
        $variant = $variants[($index - 1) % $variants->count()];
        /** @var Product $product */
        $product = $variant->product;
        $entries = PriceBookEntry::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->orderBy('min_quantity')
            ->get();
        $entry = $entries[($index - 1) % $entries->count()];
        $quantity = str_starts_with($variant->sku, 'CHAIR-') ? 10 + ($index % 5) * 5 : 2 + ($index % 6);
        $discount = $index % 4;
        $total = $quantity * (float) $entry->unit_price * (1 - $discount / 100);

        OpportunityItem::query()->where('opportunity_id', $opportunity->id)->delete();
        OpportunityItem::query()->create([
            'opportunity_id' => $opportunity->id,
            'product_id' => $product->id,
            'price_book_entry_id' => $entry->id,
            'product_name' => $product->name.' — '.($variant->name ?: $variant->sku),
            'sku' => $variant->sku,
            'quantity' => $quantity,
            'unit_price' => $entry->unit_price,
            'discount_percent' => $discount,
            'vat_percent' => $variant->vat_percent,
            'total_price' => $total,
            'notes' => 'Hạng mục nội thất demo cho báo cáo theo thời gian.',
        ]);

        $opportunity->forceFill(['amount' => round($total, -3)])->saveQuietly();
    }

    /**
     * @param  Collection<int, PipelineStage>  $stages
     */
    private function seedStageHistory(
        Opportunity $opportunity,
        PipelineStage $currentStage,
        Collection $stages,
        User $owner,
        Carbon $createdAt,
    ): void {
        OpportunityStageHistory::query()->where('opportunity_id', $opportunity->id)->delete();
        $openPath = $stages->where('is_won', false)->where('is_lost', false)->values();
        $path = $currentStage->is_won || $currentStage->is_lost
            ? $openPath->push($currentStage)
            : $openPath->takeUntil(fn (PipelineStage $stage): bool => $stage->id === $currentStage->id)
                ->push($currentStage);

        for ($position = 1; $position < $path->count(); $position++) {
            $transitionAt = $createdAt->copy()->addDays($position * 3);
            OpportunityStageHistory::query()->create([
                'opportunity_id' => $opportunity->id,
                'from_stage_id' => $path[$position - 1]->id,
                'to_stage_id' => $path[$position]->id,
                'user_id' => $owner->id,
                'notes' => 'Lịch sử demo cho báo cáo phễu.',
                'duration_seconds' => 259_200,
                'created_at' => $transitionAt->min(now()),
            ]);
        }
    }

    private function seedActivity(
        int $index,
        string $suffix,
        Opportunity $opportunity,
        User $owner,
        Carbon $createdAt,
    ): void {
        $activity = Activity::query()->updateOrCreate(
            ['title' => "Hoạt động báo cáo {$suffix}"],
            [
                'activity_type' => ActivityType::cases()[($index - 1) % count(ActivityType::cases())],
                'subject_type' => Opportunity::class,
                'subject_id' => $opportunity->id,
                'description' => 'Hoạt động khảo sát, tư vấn vật liệu hoặc theo dõi báo giá nội thất.',
                'user_id' => $owner->id,
                'performed_at' => $createdAt->copy()->addHours(2),
                'duration_minutes' => 15 + (($index % 4) * 15),
                'location' => $index % 2 === 0 ? 'Google Meet' : 'Văn phòng',
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ],
        );
        $this->setTimestamps($activity, $createdAt);
    }

    private function seedTask(
        int $index,
        string $suffix,
        Opportunity $opportunity,
        User $owner,
        Carbon $createdAt,
    ): void {
        $completed = $index % 3 !== 0;
        $task = Task::query()->updateOrCreate(
            ['title' => "Công việc báo cáo {$suffix}"],
            [
                'description' => 'Theo dõi khảo sát mặt bằng, phối cảnh, báo giá hoặc kế hoạch lắp đặt.',
                'status' => $completed ? TaskStatus::Completed : TaskStatus::InProgress,
                'priority' => TaskPriority::cases()[($index - 1) % count(TaskPriority::cases())],
                'due_date' => now()->subDays($index % 7)->addDays(1),
                'completed_at' => $completed ? now()->subDays($index % 7) : null,
                'assigned_to' => $owner->id,
                'created_by' => $owner->id,
                'subject_type' => Opportunity::class,
                'subject_id' => $opportunity->id,
            ],
        );
        $this->setTimestamps($task, $createdAt);
        $task->assignees()->syncWithoutDetaching([$owner->id]);
    }

    private function scenarioDate(int $index): Carbon
    {
        if ($index <= 7) {
            return now()->subDays($index - 1)->setTime(9 + ($index % 8), 15);
        }

        $monthOffset = ($index - 8) % 24;
        $month = now()->subMonthsNoOverflow($monthOffset)->startOfMonth();
        $day = min($month->daysInMonth, 2 + (($index * 7) % 24));

        return $month->addDays($day - 1)->setTime(8 + ($index % 9), 30);
    }

    private function lossReason(int $index): string
    {
        $reasons = [
            'Ngân sách chưa phù hợp',
            'Khách hàng chọn đối thủ',
            'Dự án tạm hoãn',
            'Không đáp ứng thời gian triển khai',
        ];

        return $reasons[$index % count($reasons)];
    }

    private function setTimestamps(Lead|Opportunity|Activity|Task $model, Carbon $createdAt): void
    {
        $model->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addHour(),
        ])->saveQuietly();
    }
}
