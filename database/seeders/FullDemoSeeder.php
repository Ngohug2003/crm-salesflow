<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\ForecastCategory;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Enums\QuoteStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\Opportunity;
use App\Models\OpportunityItem;
use App\Models\OpportunityStageHistory;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskComment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Full coherent demo seeder.
 *
 * Produces clean, semantically meaningful data across all tables:
 * Companies → Contacts → Leads (with notes/tags) → Opportunities (with items/stage history/quotes)
 * → Activities (per lead + opportunity) → Tasks (all within last 7 days)
 *
 * All records are cross-linked: contact ↔ company ↔ lead ↔ opportunity ↔ quote ↔ activities ↔ tasks
 */
final class FullDemoSeeder extends Seeder
{
    private CarbonImmutable $now;

    /** @var array<string, User> */
    private array $salesUsers = [];

    /** @var array<string, User> */
    private array $managersMap = [];

    /** @var array<int, PipelineStage> */
    private array $stages = [];

    private ?PipelineStage $wonStage = null;

    private ?PipelineStage $lostStage = null;

    public function run(): void
    {
        $this->now = CarbonImmutable::now('Asia/Ho_Chi_Minh');

        $this->loadUsers();
        $this->loadPipeline();

        DB::transaction(function (): void {
            $companies = $this->seedCompanies();
            $contacts = $this->seedContacts($companies);
            $leads = $this->seedLeads($contacts);
            $opportunities = $this->seedOpportunities($companies, $contacts, $leads);
            $this->seedOpportunityItems($opportunities);
            $this->seedQuotes($opportunities, $contacts);
            $this->seedActivities($leads, $opportunities, $contacts, $companies);
            $this->seedTasks($opportunities, $leads);
        });

        $this->command->info('✓ FullDemoSeeder: dữ liệu demo đầy đủ và liên kết đã được tạo thành công.');
    }

    // ─────────────────────────────────────────────────────────────────
    // BOOTSTRAP
    // ─────────────────────────────────────────────────────────────────

    private function loadUsers(): void
    {
        $salesEmails = [
            'demo03@salesflow.test', 'demo04@salesflow.test', 'demo05@salesflow.test',
            'demo06@salesflow.test', 'demo09@salesflow.test', 'demo10@salesflow.test',
            'demo11@salesflow.test', 'demo14@salesflow.test', 'demo15@salesflow.test',
            'demo16@salesflow.test',
        ];

        $allActive = User::query()
            ->where('is_active', true)
            ->whereIn('email', $salesEmails)
            ->orderBy('id')
            ->get();

        foreach ($allActive as $user) {
            $this->salesUsers[$user->email] = $user;
        }

        // managers (demo03 = sales manager, demo14 = marketing manager)
        $this->managersMap['sales'] = $this->salesUsers['demo03@salesflow.test']
            ?? $allActive->first()
            ?? User::query()->where('email', 'admin@salesflow.test')->firstOrFail();

        $this->managersMap['marketing'] = $this->salesUsers['demo14@salesflow.test']
            ?? $allActive->first()
            ?? User::query()->where('email', 'admin@salesflow.test')->firstOrFail();
    }

    private function loadPipeline(): void
    {
        $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
        $stageCollection = $pipeline->stages()->orderBy('position')->get();

        foreach ($stageCollection as $stage) {
            $this->stages[] = $stage;

            if ($stage->is_won) {
                $this->wonStage = $stage;
            }

            if ($stage->is_lost) {
                $this->lostStage = $stage;
            }
        }
    }

    private function pickUser(int $index): User
    {
        $users = array_values($this->salesUsers);
        if (empty($users)) {
            return User::query()->where('email', 'admin@salesflow.test')->firstOrFail();
        }

        return $users[$index % count($users)];
    }

    private function openStage(int $index): PipelineStage
    {
        $open = array_values(array_filter(
            $this->stages,
            fn (PipelineStage $s): bool => ! $s->is_won && ! $s->is_lost,
        ));

        return $open[$index % count($open)];
    }

    // ─────────────────────────────────────────────────────────────────
    // COMPANIES  (12 công ty đa ngành)
    // ─────────────────────────────────────────────────────────────────

    /** @return list<Company> */
    private function seedCompanies(): array
    {
        $data = [
            ['name' => 'Công ty Cổ phần Công nghệ Ánh Dương', 'tax' => '0312345601', 'site' => 'anhduongtech.vn', 'industry' => 'Công nghệ thông tin', 'size' => '51-200 nhân sự', 'revenue' => 3_500_000_000.0, 'city' => 'TP. Hồ Chí Minh'],
            ['name' => 'Tập đoàn Tài chính Sao Việt', 'tax' => '0312345602', 'site' => 'saovietgroup.vn', 'industry' => 'Tài chính - Ngân hàng', 'size' => '201-500 nhân sự', 'revenue' => 12_000_000_000.0, 'city' => 'Hà Nội'],
            ['name' => 'Công ty TNHH Bất động sản Minh Long', 'tax' => '0312345603', 'site' => 'minhlongland.vn', 'industry' => 'Bất động sản', 'size' => '11-50 nhân sự', 'revenue' => 2_200_000_000.0, 'city' => 'Đà Nẵng'],
            ['name' => 'Công ty Sản xuất & Thương mại Đại Phát', 'tax' => '0312345604', 'site' => 'daiphatcorp.vn', 'industry' => 'Sản xuất', 'size' => '500+ nhân sự', 'revenue' => 28_000_000_000.0, 'city' => 'Bình Dương'],
            ['name' => 'Chuỗi Bán lẻ Đông Nam', 'tax' => '0312345605', 'site' => 'dongnamretail.vn', 'industry' => 'Bán lẻ', 'size' => '51-200 nhân sự', 'revenue' => 5_400_000_000.0, 'city' => 'TP. Hồ Chí Minh'],
            ['name' => 'Công ty Giải pháp Y tế Hưng Thịnh', 'tax' => '0312345606', 'site' => 'hungthinhmed.vn', 'industry' => 'Y tế - Dược phẩm', 'size' => '11-50 nhân sự', 'revenue' => 1_800_000_000.0, 'city' => 'TP. Hồ Chí Minh'],
            ['name' => 'Tập đoàn Giáo dục Quốc tế Việt Nam', 'tax' => '0312345607', 'site' => 'viedu.vn', 'industry' => 'Giáo dục', 'size' => '201-500 nhân sự', 'revenue' => 7_200_000_000.0, 'city' => 'Hà Nội'],
            ['name' => 'Công ty Cổ phần Phần mềm Toàn Cầu', 'tax' => '0312345608', 'site' => 'globalsoft.vn', 'industry' => 'Công nghệ thông tin', 'size' => '1-10 nhân sự', 'revenue' => 480_000_000.0, 'city' => 'TP. Hồ Chí Minh'],
            ['name' => 'Công ty TNHH Logistics Nam Việt', 'tax' => '0312345609', 'site' => 'namvietlog.vn', 'industry' => 'Logistics', 'size' => '51-200 nhân sự', 'revenue' => 4_800_000_000.0, 'city' => 'TP. Hồ Chí Minh'],
            ['name' => 'Công ty Tư vấn Tài chính An Bình', 'tax' => '0312345610', 'site' => 'anbinhfin.vn', 'industry' => 'Tài chính - Ngân hàng', 'size' => '11-50 nhân sự', 'revenue' => 1_200_000_000.0, 'city' => 'Hà Nội'],
            ['name' => 'Công ty Cổ phần Đầu tư Xây dựng Bảo An', 'tax' => '0312345611', 'site' => 'baoancon.vn', 'industry' => 'Bất động sản', 'size' => '201-500 nhân sự', 'revenue' => 15_000_000_000.0, 'city' => 'TP. Hồ Chí Minh'],
            ['name' => 'Công ty TNHH Dược phẩm Thái Bình Dương', 'tax' => '0312345612', 'site' => 'pacificpharma.vn', 'industry' => 'Y tế - Dược phẩm', 'size' => '51-200 nhân sự', 'revenue' => 6_500_000_000.0, 'city' => 'Hà Nội'],
        ];

        $companies = [];

        foreach ($data as $i => $d) {
            $owner = $this->pickUser($i);
            $createdAt = $this->now->subDays(60 + $i * 3)->setTime(8 + ($i % 4), 0);

            $company = Company::query()->updateOrCreate(
                ['tax_code' => $d['tax']],
                [
                    'owner_id' => $owner->id,
                    'department_id' => $owner->department_id,
                    'name' => $d['name'],
                    'website' => 'https://' . $d['site'],
                    'email' => 'info@' . $d['site'],
                    'phone' => sprintf('02838%05d', $i + 1),
                    'industry' => $d['industry'],
                    'company_size' => $d['size'],
                    'annual_revenue' => $d['revenue'],
                    'address' => sprintf('%d Đường Nguyễn Văn Linh, Phường %d', ($i + 1) * 10, $i + 1),
                    'city' => $d['city'],
                    'province' => $d['city'],
                    'country' => 'Việt Nam',
                    'notes' => "Đối tác tiềm năng trong lĩnh vực {$d['industry']}. Đã được xác minh thông tin pháp lý.",
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );

            $company->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
            $companies[] = $company;
        }

        return $companies;
    }

    // ─────────────────────────────────────────────────────────────────
    // CONTACTS  (2-3 người liên hệ mỗi công ty)
    // ─────────────────────────────────────────────────────────────────

    /**
     * @param  list<Company>  $companies
     * @return list<Contact>
     */
    private function seedContacts(array $companies): array
    {
        $contactDefs = [
            ['last' => 'Nguyễn', 'first' => 'Tuấn Anh', 'title' => 'Giám đốc Kinh doanh', 'dept' => 'Kinh doanh'],
            ['last' => 'Trần', 'first' => 'Thị Bích', 'title' => 'Trưởng phòng Mua hàng', 'dept' => 'Mua hàng'],
            ['last' => 'Lê', 'first' => 'Minh Khoa', 'title' => 'CEO', 'dept' => 'Ban lãnh đạo'],
            ['last' => 'Phạm', 'first' => 'Thùy Dung', 'title' => 'Giám đốc Marketing', 'dept' => 'Marketing'],
            ['last' => 'Hoàng', 'first' => 'Văn Tùng', 'title' => 'Trưởng phòng IT', 'dept' => 'Công nghệ thông tin'],
            ['last' => 'Vũ', 'first' => 'Khánh Linh', 'title' => 'Phó Giám đốc Tài chính', 'dept' => 'Tài chính'],
            ['last' => 'Đỗ', 'first' => 'Quốc Hùng', 'title' => 'Quản lý Dự án', 'dept' => 'Vận hành'],
            ['last' => 'Bùi', 'first' => 'Ngọc Mai', 'title' => 'Chuyên viên Mua hàng', 'dept' => 'Mua hàng'],
            ['last' => 'Ngô', 'first' => 'Thanh Bình', 'title' => 'CTO', 'dept' => 'Công nghệ thông tin'],
            ['last' => 'Đặng', 'first' => 'Thu Hương', 'title' => 'Giám đốc Nhân sự', 'dept' => 'Nhân sự'],
            ['last' => 'Dương', 'first' => 'Hải Long', 'title' => 'Trưởng phòng Kinh doanh', 'dept' => 'Kinh doanh'],
            ['last' => 'Lý', 'first' => 'Quang Vinh', 'title' => 'Giám đốc Điều hành', 'dept' => 'Ban lãnh đạo'],
            ['last' => 'Hồ', 'first' => 'Bảo Trâm', 'title' => 'Chuyên viên Tư vấn', 'dept' => 'Kinh doanh'],
            ['last' => 'Phan', 'first' => 'Lan Anh', 'title' => 'Trưởng phòng Hành chính', 'dept' => 'Hành chính'],
            ['last' => 'Mai', 'first' => 'Tuấn Kiệt', 'title' => 'Giám đốc Vận hành', 'dept' => 'Vận hành'],
            ['last' => 'Tạ', 'first' => 'Ngọc Hân', 'title' => 'Chuyên viên Tài chính', 'dept' => 'Tài chính'],
            ['last' => 'Cao', 'first' => 'Nhật Quang', 'title' => 'Trưởng phòng Kỹ thuật', 'dept' => 'Kỹ thuật'],
            ['last' => 'Đinh', 'first' => 'Mỹ Duyên', 'title' => 'Giám đốc Marketing', 'dept' => 'Marketing'],
            ['last' => 'Trịnh', 'first' => 'Anh Khoa', 'title' => 'Trưởng phòng Pháp lý', 'dept' => 'Pháp lý'],
            ['last' => 'Võ', 'first' => 'Hoàng Nam', 'title' => 'CFO', 'dept' => 'Tài chính'],
            ['last' => 'Nguyễn', 'first' => 'Thành Đạt', 'title' => 'Trưởng nhóm Sales', 'dept' => 'Kinh doanh'],
            ['last' => 'Trần', 'first' => 'Minh Châu', 'title' => 'Phó phòng Mua hàng', 'dept' => 'Mua hàng'],
            ['last' => 'Lê', 'first' => 'Thu Giang', 'title' => 'Giám đốc Dự án', 'dept' => 'Vận hành'],
            ['last' => 'Phạm', 'first' => 'Gia Bảo', 'title' => 'Chuyên viên IT', 'dept' => 'Công nghệ thông tin'],
        ];

        $contacts = [];

        foreach ($contactDefs as $i => $def) {
            $company = $companies[$i % count($companies)];
            $num = $i + 1;
            $createdAt = $this->now->subDays(55 + $i * 2)->setTime(9, 0);

            $contact = Contact::query()->updateOrCreate(
                ['email' => sprintf('contact.%02d@salesflow.test', $num)],
                [
                    'company_id' => $company->id,
                    'owner_id' => $company->owner_id,
                    'department_id' => $company->department_id,
                    'first_name' => $def['first'],
                    'last_name' => $def['last'],
                    'full_name' => $def['last'] . ' ' . $def['first'],
                    'phone' => sprintf('0909%06d', $num),
                    'secondary_phone' => sprintf('0912%06d', $num),
                    'job_title' => $def['title'],
                    'department_name' => $def['dept'],
                    'birthday' => sprintf('19%02d-0%d-%02d', 70 + ($i % 20), ($i % 9) + 1, ($i % 27) + 1),
                    'is_primary' => $i % 2 === 0,
                    'address' => sprintf('%d Lê Lợi', $num * 3),
                    'city' => $company->city,
                    'province' => $company->city,
                    'country' => 'Việt Nam',
                    'notes' => "Người liên hệ chính phụ trách quyết định mua hàng tại {$company->name}.",
                    'created_by' => $company->created_by,
                    'updated_by' => $company->updated_by,
                ],
            );

            $contact->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
            $contacts[] = $contact;
        }

        return $contacts;
    }

    // ─────────────────────────────────────────────────────────────────
    // LEADS  (30 leads liên kết với contact + company)
    // ─────────────────────────────────────────────────────────────────

    /**
     * @param  list<Contact>  $contacts
     * @return list<Lead>
     */
    private function seedLeads(array $contacts): array
    {
        /** @var Collection<int, \App\Models\LeadSource> $sources */
        $sources = \App\Models\LeadSource::query()->where('is_active', true)->orderBy('sort_order')->get();
        /** @var Collection<int, Tag> $tags */
        $tags = Tag::query()->get();

        $names = [
            'Nguyễn Hoàng Anh', 'Trần Minh Châu', 'Lê Quốc Đạt', 'Phạm Thu Giang', 'Hoàng Gia Hân',
            'Đỗ Minh Khang', 'Vũ Ngọc Lan', 'Bùi Thành Long', 'Ngô Quỳnh Mai', 'Đặng Đức Nam',
            'Dương Khánh Ngân', 'Lý Tuấn Phong', 'Hồ Bảo Quân', 'Phan Thùy Trang', 'Mai Anh Tú',
            'Tạ Hải Yến', 'Cao Nhật Minh', 'Đinh Thanh Hà', 'Trịnh Quốc Huy', 'Võ Phương Linh',
            'Nguyễn Công Thành', 'Trần Mỹ Duyên', 'Lê Trung Kiên', 'Phạm Bích Ngọc', 'Hoàng Tuấn Vũ',
            'Đỗ Kim Oanh', 'Vũ Đức Thắng', 'Bùi Hà My', 'Ngô Quang Vinh', 'Đặng Thu Thủy',
        ];

        $companies = ['Công ty Ánh Dương', 'Tập đoàn Sao Việt', 'Công ty Minh Long', 'Đại Phát Corp', 'Đông Nam Retail', 'Hưng Thịnh Med'];
        $jobTitles = ['Giám đốc', 'Trưởng phòng Kinh doanh', 'Quản lý Mua hàng', 'Chuyên viên Marketing', 'Chủ doanh nghiệp'];
        $statuses = LeadStatus::cases();
        $priorities = LeadPriority::cases();

        $leads = [];

        foreach ($names as $i => $name) {
            $num = $i + 1;
            $owner = $this->pickUser($i);
            $status = $statuses[$i % count($statuses)];
            // Leads created between 7-50 days ago
            $createdAt = $this->now->subDays(7 + $i)->setTime(8 + ($i % 6), 30);
            $contact = $contacts[$i % count($contacts)] ?? null;

            $lead = Lead::query()->updateOrCreate(
                ['email' => sprintf('lead.%02d@salesflow.test', $num)],
                [
                    'lead_source_id' => $sources->isNotEmpty() ? $sources->values()[$i % $sources->count()]->id : null,
                    'owner_id' => $owner->id,
                    'department_id' => $owner->department_id,
                    'full_name' => $name,
                    'phone' => sprintf('0908%06d', $num),
                    'secondary_phone' => $i % 3 === 0 ? sprintf('0905%06d', $num) : null,
                    'company_name' => $companies[$i % count($companies)],
                    'job_title' => $jobTitles[$i % count($jobTitles)],
                    'website' => null,
                    'address' => sprintf('%d Đường Nguyễn Huệ', $num),
                    'city' => $i % 4 === 0 ? 'Hà Nội' : 'TP. Hồ Chí Minh',
                    'province' => $i % 4 === 0 ? 'Hà Nội' : 'TP. Hồ Chí Minh',
                    'country' => 'Việt Nam',
                    'status' => $status,
                    'priority' => $priorities[$i % count($priorities)],
                    'score' => 20 + ($i * 3 % 80),
                    'estimated_value' => (string) (15_000_000 + ($i * 8_000_000)),
                    'notes' => "Lead đến từ kênh " . ($sources->isNotEmpty() ? $sources->values()[$i % $sources->count()]->name : 'Online') . ". Quan tâm đến giải pháp CRM doanh nghiệp.",
                    'converted_at' => $status === LeadStatus::Converted ? $createdAt->addDays(5) : null,
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );

            $lead->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt->addHours(2)])->saveQuietly();

            // Tags — 2 per lead
            if ($tags->isNotEmpty()) {
                $tagIds = [
                    $tags->values()[$i % $tags->count()]->id,
                    $tags->values()[($i + 2) % $tags->count()]->id,
                ];
                $lead->tags()->sync(array_unique($tagIds));
            }

            // Lead notes — 1-2 per lead
            LeadNote::query()->updateOrCreate(
                ['lead_id' => $lead->id, 'user_id' => $owner->id],
                [
                    'content' => $this->leadNoteContent($i, $name),
                    'is_pinned' => $i % 5 === 0,
                ],
            );

            if ($i % 3 === 0) {
                LeadNote::query()->create([
                    'lead_id' => $lead->id,
                    'user_id' => $this->managersMap['sales']->id,
                    'content' => "Manager review: Lead có tiềm năng chuyển đổi cao. Ưu tiên theo dõi trong tuần này.",
                    'is_pinned' => true,
                ]);
            }

            $leads[] = $lead;
        }

        return $leads;
    }

    // ─────────────────────────────────────────────────────────────────
    // OPPORTUNITIES  (20 cơ hội đa dạng stage, liên kết đầy đủ)
    // ─────────────────────────────────────────────────────────────────

    /**
     * @param  list<Company>   $companies
     * @param  list<Contact>   $contacts
     * @param  list<Lead>      $leads
     * @return list<Opportunity>
     */
    private function seedOpportunities(array $companies, array $contacts, array $leads): array
    {
        $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();

        $oppDefs = [
            // [title, amount, stage_code, is_won, is_lost, forecastCat, daysAgo, closeDayOffset, lostReason]
            ['Triển khai CRM Doanh nghiệp cho Ánh Dương Tech', 280_000_000, 'closed-won', true, false, ForecastCategory::Closed, 45, -5, null],
            ['Cung cấp Giải pháp ERP tích hợp cho Sao Việt Group', 520_000_000, 'negotiation', false, false, ForecastCategory::Commit, 30, 15, null],
            ['Nâng cấp Hệ thống Quản lý Bán lẻ Đông Nam', 185_000_000, 'proposal-quote', false, false, ForecastCategory::BestCase, 20, 20, null],
            ['Tư vấn Chuyển đổi Số cho Đại Phát Corp', 350_000_000, 'needs-analysis', false, false, ForecastCategory::Pipeline, 15, 30, null],
            ['Triển khai Platform Học trực tuyến VieEdu', 240_000_000, 'closed-lost', false, true, ForecastCategory::Closed, 60, -10, 'Khách hàng chọn giải pháp nguồn mở chi phí thấp hơn'],
            ['Dự án Tự động hóa Kho vận Nam Việt Logistics', 168_000_000, 'initial-contact', false, false, ForecastCategory::Pipeline, 7, 45, null],
            ['Hệ thống BI và Phân tích Dữ liệu An Bình Fin', 420_000_000, 'proposal-quote', false, false, ForecastCategory::BestCase, 25, 18, null],
            ['Triển khai LMS Quản lý Đào tạo Nội bộ Bảo An', 145_000_000, 'closed-won', true, false, ForecastCategory::Closed, 55, -8, null],
            ['Giải pháp CRM Dược phẩm cho Thái Bình Dương', 310_000_000, 'negotiation', false, false, ForecastCategory::Commit, 18, 12, null],
            ['Phần mềm Quản lý Bệnh viện Hưng Thịnh Med', 380_000_000, 'needs-analysis', false, false, ForecastCategory::Pipeline, 12, 35, null],
            ['Nền tảng Thương mại Điện tử B2B Minh Long', 195_000_000, 'proposal-quote', false, false, ForecastCategory::BestCase, 22, 22, null],
            ['Tích hợp API Thanh toán Sao Việt - VNPay', 85_000_000, 'closed-won', true, false, ForecastCategory::Closed, 40, -3, null],
            ['Hệ thống Chăm sóc Khách hàng Đông Nam 360', 225_000_000, 'negotiation', false, false, ForecastCategory::Commit, 28, 10, null],
            ['Dự án IoT Giám sát Dây chuyền Đại Phát', 460_000_000, 'needs-analysis', false, false, ForecastCategory::Pipeline, 10, 40, null],
            ['Ứng dụng Di động Bán hàng Đại lý Toàn Cầu', 120_000_000, 'initial-contact', false, false, ForecastCategory::Pipeline, 5, 60, null],
            ['Tư vấn An toàn Thông tin cho Ánh Dương Tech', 95_000_000, 'closed-lost', false, true, ForecastCategory::Closed, 50, -15, 'Ngân sách bị cắt giảm do tái cơ cấu nội bộ'],
            ['Hệ thống Quản lý Tài sản Bảo An Construction', 340_000_000, 'proposal-quote', false, false, ForecastCategory::BestCase, 16, 25, null],
            ['Platform Telehealth Hưng Thịnh - Giai đoạn 2', 285_000_000, 'negotiation', false, false, ForecastCategory::Commit, 35, 8, null],
            ['Triển khai Zalo Mini App Bán hàng Nam Việt', 75_000_000, 'initial-contact', false, false, ForecastCategory::Pipeline, 3, 30, null],
            ['Nâng cấp Data Warehouse An Bình Fin Q3/2026', 210_000_000, 'proposal-quote', false, false, ForecastCategory::BestCase, 14, 20, null],
        ];

        $opportunities = [];

        foreach ($oppDefs as $i => $def) {
            [$title, $amount, $stageCode, $isWon, $isLost, $forecastCat, $daysAgo, $closeDayOffset, $lostReason] = $def;

            $company = $companies[$i % count($companies)];
            $contact = $contacts[$i % count($contacts)];
            $lead = isset($leads[$i]) ? $leads[$i] : ($leads[$i % count($leads)] ?? null);
            $owner = $this->pickUser($i);
            $code = sprintf('OPP-%04d', $i + 1);

            // Resolve stage
            $stage = $this->resolveStage($stageCode);

            $createdAt = $this->now->subDays($daysAgo)->setTime(9, 0);
            $actualCloseDate = ($isWon || $isLost)
                ? $createdAt->addDays(abs($closeDayOffset))->toDateString()
                : null;
            $expectedCloseDate = ($isWon || $isLost)
                ? $actualCloseDate
                : $this->now->addDays(abs($closeDayOffset))->toDateString();

            $opp = Opportunity::query()->updateOrCreate(
                ['code' => $code],
                [
                    'title' => $title,
                    'amount' => $amount,
                    'pipeline_id' => $pipeline->id,
                    'stage_id' => $stage->id,
                    'company_id' => $company->id,
                    'contact_id' => $contact->id,
                    'lead_id' => $lead?->id,
                    'owner_id' => $owner->id,
                    'department_id' => $owner->department_id,
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                    'expected_close_date' => $expectedCloseDate,
                    'actual_close_date' => $actualCloseDate,
                    'lost_reason' => $lostReason,
                    'notes' => $this->oppNote($i, $company->name),
                    'is_won' => $isWon,
                    'is_lost' => $isLost,
                    'forecast_category' => $forecastCat,
                ],
            );

            $opp->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt->addHour()])->saveQuietly();

            // Stage history
            $this->seedStageHistory($opp, $stage, $owner, $createdAt);

            $opportunities[] = $opp;
        }

        return $opportunities;
    }

    private function resolveStage(string $code): PipelineStage
    {
        foreach ($this->stages as $stage) {
            if ($stage->code === $code) {
                return $stage;
            }
        }

        return $this->stages[0];
    }

    private function seedStageHistory(Opportunity $opp, PipelineStage $currentStage, User $user, CarbonImmutable $createdAt): void
    {
        OpportunityStageHistory::query()->where('opportunity_id', $opp->id)->delete();

        $openStages = array_values(array_filter($this->stages, fn (PipelineStage $s): bool => ! $s->is_won && ! $s->is_lost));
        $path = [];

        if ($currentStage->is_won || $currentStage->is_lost) {
            $path = [...$openStages, $currentStage];
        } else {
            foreach ($openStages as $s) {
                $path[] = $s;

                if ($s->id === $currentStage->id) {
                    break;
                }
            }
        }

        for ($pos = 1; $pos < count($path); $pos++) {
            OpportunityStageHistory::query()->create([
                'opportunity_id' => $opp->id,
                'from_stage_id' => $path[$pos - 1]->id,
                'to_stage_id' => $path[$pos]->id,
                'user_id' => $user->id,
                'notes' => "Chuyển giai đoạn theo quy trình bán hàng.",
                'duration_seconds' => 86400 * (2 + $pos),
                'created_at' => $createdAt->addDays($pos * 4)->min($this->now),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // OPPORTUNITY ITEMS  (2-4 dòng sản phẩm mỗi cơ hội)
    // ─────────────────────────────────────────────────────────────────

    /** @param list<Opportunity> $opportunities */
    private function seedOpportunityItems(array $opportunities): void
    {
        $productCatalog = [
            ['name' => 'Phần mềm CRM SalesFlow Pro', 'sku' => 'CRM-PRO-001', 'price' => 45_000_000],
            ['name' => 'Module Quản lý Lead & Pipeline', 'sku' => 'CRM-MOD-002', 'price' => 18_000_000],
            ['name' => 'Module Báo cáo & Phân tích', 'sku' => 'CRM-MOD-003', 'price' => 22_000_000],
            ['name' => 'Tích hợp Zalo OA / Chatbot', 'sku' => 'INT-ZALO-004', 'price' => 15_000_000],
            ['name' => 'Dịch vụ Cài đặt & Cấu hình', 'sku' => 'SVC-SETUP-005', 'price' => 12_000_000],
            ['name' => 'Đào tạo người dùng (10 buổi)', 'sku' => 'SVC-TRAIN-006', 'price' => 8_000_000],
            ['name' => 'Hỗ trợ kỹ thuật 12 tháng', 'sku' => 'SVC-SUP-007', 'price' => 10_000_000],
            ['name' => 'Module Marketing Automation', 'sku' => 'CRM-MOD-008', 'price' => 30_000_000],
            ['name' => 'Tích hợp ERP / Kế toán', 'sku' => 'INT-ERP-009', 'price' => 25_000_000],
            ['name' => 'Hosting Cloud 1 năm', 'sku' => 'INFRA-HOST-010', 'price' => 6_000_000],
        ];

        foreach ($opportunities as $i => $opp) {
            OpportunityItem::query()->where('opportunity_id', $opp->id)->delete();

            $itemCount = 2 + ($i % 3); // 2, 3 or 4 items

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $productCatalog[($i + $j) % count($productCatalog)];
                $qty = 1 + ($j % 2);
                $discount = $j === 0 ? 0 : (5 + ($i % 10));
                $unitPrice = $product['price'];
                $totalPrice = $qty * $unitPrice * (1 - $discount / 100);

                OpportunityItem::query()->create([
                    'opportunity_id' => $opp->id,
                    'product_name' => $product['name'],
                    'sku' => $product['sku'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_percent' => $discount,
                    'total_price' => $totalPrice,
                    'notes' => $j === 0 ? 'Hạng mục chính theo yêu cầu khách hàng.' : null,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // QUOTES  (1 báo giá mỗi opportunity có stage >= proposal-quote)
    // ─────────────────────────────────────────────────────────────────

    /**
     * @param  list<Opportunity>  $opportunities
     * @param  list<Contact>      $contacts
     */
    private function seedQuotes(array $opportunities, array $contacts): void
    {
        $quoteStatuses = [
            QuoteStatus::Accepted,
            QuoteStatus::Sent,
            QuoteStatus::Draft,
            QuoteStatus::Sent,
            QuoteStatus::Rejected,
        ];

        $quoteableStages = ['proposal-quote', 'negotiation', 'closed-won', 'closed-lost'];
        $quoteNum = 1;

        foreach ($opportunities as $i => $opp) {
            $stage = $this->resolveStage($this->stageCodeForOpp($i));
            $isQuoteable = in_array($stage->code, $quoteableStages, true);

            if (! $isQuoteable) {
                continue;
            }

            Quote::query()->where('opportunity_id', $opp->id)->forceDelete();

            $status = $opp->is_won ? QuoteStatus::Accepted
                : ($opp->is_lost ? QuoteStatus::Rejected
                : $quoteStatuses[$i % count($quoteStatuses)]);

            $contact = $contacts[$i % count($contacts)];
            $createdAt = $this->now->subDays(max(1, (int) ($opp->created_at?->diffInDays($this->now) ?? 5) - 3));
            $validUntil = $createdAt->addDays(30)->toDateString();

            $subtotal = (float) $opp->amount * 0.92;
            $taxPercent = 10.0;
            $taxAmount = $subtotal * $taxPercent / 100;
            $discountAmt = (float) $opp->amount * 0.05;
            $totalAmount = $subtotal + $taxAmount - $discountAmt;

            $quote = Quote::query()->create([
                'quote_number' => sprintf('QUO-%04d', $quoteNum++),
                'opportunity_id' => $opp->id,
                'company_id' => $opp->company_id,
                'contact_id' => $contact->id,
                'status' => $status,
                'valid_until' => $validUntil,
                'subtotal' => $subtotal,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmt,
                'total_amount' => $totalAmount,
                'notes' => "Báo giá chính thức gửi đến {$contact->full_name}. Giá đã bao gồm thuế GTGT 10%.",
                'created_by' => $opp->owner_id,
                'updated_by' => $opp->owner_id,
            ]);

            $quote->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

            // Quote items (2-3 items)
            $products = [
                ['name' => 'Phần mềm CRM SalesFlow Pro', 'sku' => 'CRM-PRO-001', 'price' => 45_000_000, 'qty' => 1, 'disc' => 0],
                ['name' => 'Dịch vụ Cài đặt & Cấu hình', 'sku' => 'SVC-SETUP-005', 'price' => 12_000_000, 'qty' => 1, 'disc' => 5],
                ['name' => 'Hỗ trợ kỹ thuật 12 tháng', 'sku' => 'SVC-SUP-007', 'price' => 10_000_000, 'qty' => 1, 'disc' => 10],
            ];

            $itemCount = 2 + ($i % 2);

            for ($j = 0; $j < $itemCount; $j++) {
                $p = $products[$j % count($products)];
                $lineTotal = $p['qty'] * $p['price'] * (1 - $p['disc'] / 100);

                QuoteItem::query()->create([
                    'quote_id' => $quote->id,
                    'product_name' => $p['name'],
                    'sku' => $p['sku'],
                    'quantity' => $p['qty'],
                    'unit_price' => $p['price'],
                    'discount_percent' => $p['disc'],
                    'total_price' => $lineTotal,
                    'notes' => null,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // ACTIVITIES  (đa dạng loại, gắn với leads + opportunities + contacts + companies)
    // ─────────────────────────────────────────────────────────────────

    /**
     * @param  list<Lead>         $leads
     * @param  list<Opportunity>  $opportunities
     * @param  list<Contact>      $contacts
     * @param  list<Company>      $companies
     */
    private function seedActivities(array $leads, array $opportunities, array $contacts, array $companies): void
    {
        $activityTypes = ActivityType::cases();
        $locations = ['Google Meet', 'Văn phòng Khách hàng', 'Điện thoại', 'Email', 'Zoom', 'Văn phòng SalesFlow'];

        // Activities gắn với leads (10 leads)
        foreach (array_slice($leads, 0, 10) as $i => $lead) {
            $owner = $this->pickUser($i);
            $type = $activityTypes[$i % count($activityTypes)];
            $performedAt = $this->now->subDays($i % 7)->setTime(10, 0);

            Activity::query()->updateOrCreate(
                ['title' => "Tiếp cận Lead: {$lead->full_name}"],
                [
                    'activity_type' => $type,
                    'subject_type' => Lead::class,
                    'subject_id' => $lead->id,
                    'description' => $this->activityDesc($type, $lead->full_name, $lead->company_name ?? ''),
                    'user_id' => $owner->id,
                    'performed_at' => $performedAt,
                    'duration_minutes' => 15 + (($i % 4) * 15),
                    'location' => $locations[$i % count($locations)],
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );
        }

        // Activities gắn với opportunities (10 opps)
        foreach (array_slice($opportunities, 0, 10) as $i => $opp) {
            $owner = $this->pickUser($i);
            $type = $i % 2 === 0 ? ActivityType::Demo : ActivityType::Call;

            Activity::query()->updateOrCreate(
                ['title' => "Tương tác cơ hội: {$opp->title}"],
                [
                    'activity_type' => $type,
                    'subject_type' => Opportunity::class,
                    'subject_id' => $opp->id,
                    'description' => "Trao đổi chi tiết giải pháp cho đại diện {$opp->company?->name}. Thống nhất yêu cầu kỹ thuật.",
                    'user_id' => $owner->id,
                    'performed_at' => $this->now->subDays(max(1, $i % 6))->setTime(14, 0),
                    'duration_minutes' => 45,
                    'location' => $locations[$i % count($locations)],
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );
        }

        // Activities gắn với companies (5 companies)
        foreach (array_slice($companies, 0, 5) as $i => $company) {
            $owner = $this->pickUser($i);

            Activity::query()->updateOrCreate(
                ['title' => "Họp khảo sát với {$company->name}"],
                [
                    'activity_type' => ActivityType::Meeting,
                    'subject_type' => Company::class,
                    'subject_id' => $company->id,
                    'description' => "Cuộc họp khảo sát nhu cầu và đánh giá ngân sách với đại diện doanh nghiệp.",
                    'user_id' => $owner->id,
                    'performed_at' => $this->now->subDays(2 + $i)->setTime(9, 0),
                    'duration_minutes' => 60,
                    'location' => 'Văn phòng Khách hàng',
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // TASKS  (tất cả trong vòng 7 ngày, liên kết opportunity + lead)
    // ─────────────────────────────────────────────────────────────────

    /**
     * @param  list<Opportunity>  $opportunities
     * @param  list<Lead>         $leads
     */
    private function seedTasks(array $opportunities, array $leads): void
    {
        $taskTemplates = [
            // Opportunity tasks
            ['Gửi báo giá chi tiết đến khách hàng', 'Chuẩn bị và gửi báo giá đầy đủ với danh sách sản phẩm, điều khoản thanh toán, và điều kiện bảo hành.', TaskPriority::High, 'opp'],
            ['Lên lịch demo sản phẩm với đội kỹ thuật', 'Phối hợp SA và presales chuẩn bị kịch bản demo 60 phút, bao gồm use case thực tế của khách hàng.', TaskPriority::Medium, 'opp'],
            ['Soạn thảo điều khoản hợp đồng', 'Chuẩn bị draft hợp đồng bao gồm SLA, điều kiện thanh toán, và phạm vi dịch vụ theo yêu cầu đã thống nhất.', TaskPriority::High, 'opp'],
            ['Follow-up sau demo với khách hàng', 'Liên hệ sau buổi demo để thu thập phản hồi, giải đáp thắc mắc, và đề xuất bước tiếp theo.', TaskPriority::Medium, 'opp'],
            ['Chuẩn bị hồ sơ năng lực kỹ thuật', 'Tổng hợp tài liệu kỹ thuật: kiến trúc hệ thống, tài liệu tích hợp API, và checklist bảo mật.', TaskPriority::Low, 'opp'],
            ['Xác nhận ngân sách và quyết định mua hàng', 'Trao đổi với CFO/CEO khách hàng về ngân sách phê duyệt và quy trình phê duyệt nội bộ.', TaskPriority::Urgent, 'opp'],
            ['Chuẩn bị kế hoạch triển khai chi tiết', 'Soạn project plan, phân công team, và timeline từng milestone triển khai.', TaskPriority::High, 'opp'],
            ['Kiểm tra điều kiện ký kết hợp đồng', 'Xem xét lại tất cả điều khoản pháp lý, xác nhận người ký và thủ tục công chứng nếu cần.', TaskPriority::Medium, 'opp'],
            ['Gửi email xác nhận sau cuộc họp', 'Tóm tắt nội dung cuộc họp, các action items, và next steps đã thống nhất.', TaskPriority::Low, 'opp'],
            ['Cập nhật thông tin CRM sau tiếp xúc', 'Nhập đầy đủ thông tin liên hệ, ghi chú cuộc hội thoại, và cập nhật stage trong hệ thống.', TaskPriority::Low, 'opp'],
            // Lead tasks
            ['Xác minh thông tin liên hệ của Lead', 'Gọi điện hoặc email để xác nhận số điện thoại, email, và chức danh của người liên hệ.', TaskPriority::High, 'lead'],
            ['Nghiên cứu nhu cầu và background của Lead', 'Tìm hiểu về công ty, ngành nghề, quy mô, và các vấn đề tiềm năng trước khi tiếp cận.', TaskPriority::Medium, 'lead'],
            ['Gửi tài liệu giới thiệu sản phẩm', 'Gửi brochure, case study, và video demo ngắn phù hợp với ngành nghề của Lead.', TaskPriority::Medium, 'lead'],
            ['Đặt lịch cuộc gọi tư vấn đầu tiên', 'Liên hệ để thu xếp cuộc gọi 30 phút tư vấn nhu cầu và giới thiệu giải pháp.', TaskPriority::High, 'lead'],
            ['Nhập lead vào quy trình nuôi dưỡng', 'Thêm lead vào chuỗi email tự động và thiết lập lịch follow-up định kỳ.', TaskPriority::Low, 'lead'],
        ];

        $taskIdx = 0;
        $statuses = [TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::Completed, TaskStatus::InProgress, TaskStatus::Todo];

        // Tasks gắn opportunities (max 10 opp × 2 tasks)
        foreach (array_slice($opportunities, 0, 10) as $i => $opp) {
            $owner = $this->pickUser($i);

            for ($t = 0; $t < 2; $t++) {
                $template = $taskTemplates[$taskIdx % count($taskTemplates)];
                $taskIdx++;

                $status = $statuses[$taskIdx % count($statuses)];
                $daysFromNow = -3 + $taskIdx % 7; // -3 to +3 days from today
                $dueDate = $this->now->addDays($daysFromNow)->toDateTimeString();
                $completedAt = $status === TaskStatus::Completed ? $this->now->subDays(1)->toDateTimeString() : null;

                // Created within last 7 days
                $createdAt = $this->now->subDays(7 - $i % 7)->setTime(8 + ($taskIdx % 4), 0);

                $task = Task::query()->updateOrCreate(
                    [
                        'title' => $template[0] . " — {$opp->title}",
                        'created_by' => $owner->id,
                    ],
                    [
                        'description' => $template[1],
                        'status' => $status,
                        'priority' => $template[2],
                        'due_date' => $dueDate,
                        'completed_at' => $completedAt,
                        'assigned_to' => $owner->id,
                        'created_by' => $owner->id,
                        'subject_type' => Opportunity::class,
                        'subject_id' => $opp->id,
                    ],
                );

                $task->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
                $task->assignees()->syncWithoutDetaching([$owner->id]);

                // Task checklist (1-2 items per task)
                $this->seedTaskChecklist($task, $i, $status);
                // Task comment
                $this->seedTaskComment($task, $owner);
            }
        }

        // Tasks gắn leads (max 10 leads × 1 task)
        foreach (array_slice($leads, 0, 10) as $i => $lead) {
            $owner = $this->pickUser($i);
            $template = $taskTemplates[10 + $i % 5]; // lead-specific templates
            $taskIdx++;

            $status = $statuses[$taskIdx % count($statuses)];
            $dueDate = $this->now->addDays($i % 5)->toDateTimeString();
            $completedAt = $status === TaskStatus::Completed ? $this->now->subDays(1)->toDateTimeString() : null;
            $createdAt = $this->now->subDays(6 - $i % 6)->setTime(7 + ($i % 3), 30);

            $task = Task::query()->updateOrCreate(
                [
                    'title' => $template[0] . " — {$lead->full_name}",
                    'created_by' => $owner->id,
                ],
                [
                    'description' => $template[1],
                    'status' => $status,
                    'priority' => $template[2],
                    'due_date' => $dueDate,
                    'completed_at' => $completedAt,
                    'assigned_to' => $owner->id,
                    'created_by' => $owner->id,
                    'subject_type' => Lead::class,
                    'subject_id' => $lead->id,
                ],
            );

            $task->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
            $task->assignees()->syncWithoutDetaching([$owner->id]);
            $this->seedTaskComment($task, $owner);
        }
    }

    private function seedTaskChecklist(Task $task, int $i, TaskStatus $status): void
    {
        $checklists = [
            ['Xem lại yêu cầu và tài liệu liên quan', true],
            ['Chuẩn bị nội dung / tài liệu cần thiết', $status === TaskStatus::Completed],
            ['Gửi / thực hiện và xác nhận kết quả', $status === TaskStatus::Completed],
        ];

        $count = 2 + ($i % 2);

        TaskChecklist::query()->where('task_id', $task->id)->delete();

        foreach (array_slice($checklists, 0, $count) as $pos => $item) {
            TaskChecklist::query()->create([
                'task_id' => $task->id,
                'title' => $item[0],
                'is_completed' => $item[1],
                'completed_at' => $item[1] ? now()->subHours(2) : null,
                'position' => $pos + 1,
                'created_by' => $task->created_by,
            ]);
        }
    }

    private function seedTaskComment(Task $task, User $user): void
    {
        $comments = [
            'Đã liên hệ với khách hàng, đang chờ phản hồi.',
            'Hoàn thành bước chuẩn bị, chuyển sang thực hiện.',
            'Cần phối hợp thêm với team kỹ thuật để hoàn thiện.',
            'Khách hàng yêu cầu điều chỉnh nhỏ trước khi xác nhận.',
            'Đã gửi xong, đang theo dõi.',
        ];

        TaskComment::query()->updateOrCreate(
            ['task_id' => $task->id, 'user_id' => $user->id],
            ['content' => $comments[$task->id % count($comments)]],
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────

    private function stageCodeForOpp(int $i): string
    {
        $codes = [
            'closed-won', 'negotiation', 'proposal-quote', 'needs-analysis', 'closed-lost',
            'initial-contact', 'proposal-quote', 'closed-won', 'negotiation', 'needs-analysis',
            'proposal-quote', 'closed-won', 'negotiation', 'needs-analysis', 'initial-contact',
            'closed-lost', 'proposal-quote', 'negotiation', 'initial-contact', 'proposal-quote',
        ];

        return $codes[$i % count($codes)];
    }

    private function leadNoteContent(int $i, string $name): string
    {
        $notes = [
            "Đã gọi điện lần đầu, {$name} quan tâm đến module quản lý pipeline. Hẹn gặp tuần tới.",
            "Lead từ sự kiện Tech Expo. {$name} đang tìm giải pháp thay thế Excel. Rất tiềm năng.",
            "Gửi email giới thiệu, {$name} phản hồi muốn xem demo. Đặt lịch thứ 4 tuần này.",
            "Cuộc gọi tư vấn 20 phút. {$name} ngân sách 200-300 triệu, muốn triển khai trong Q3.",
            "Lead từ referral của khách hàng cũ. {$name} đã dùng giải pháp cũ 3 năm, muốn nâng cấp.",
        ];

        return $notes[$i % count($notes)];
    }

    private function oppNote(int $i, string $companyName): string
    {
        $notes = [
            "Cơ hội đến từ chiến dịch marketing tháng 7. {$companyName} có ngân sách phê duyệt sẵn.",
            "Referral từ đối tác tích hợp. Đội kỹ thuật {$companyName} đã review tài liệu kỹ thuật.",
            "Khách hàng cũ mở rộng scope. {$companyName} hài lòng với giai đoạn 1, ký thêm gói nâng cao.",
            "Cold outreach qua LinkedIn. {$companyName} đang so sánh 3 nhà cung cấp, cần thuyết phục thêm.",
            "Lead chuyển đổi từ hội thảo. {$companyName} đặt ưu tiên cao cho tích hợp với hệ thống ERP hiện có.",
        ];

        return $notes[$i % count($notes)];
    }

    private function activityDesc(ActivityType $type, string $name, string $company): string
    {
        return match ($type) {
            ActivityType::Call => "Gọi điện tư vấn với {$name} từ {$company}. Trao đổi về nhu cầu hiện tại và ngân sách dự kiến.",
            ActivityType::Meeting => "Cuộc họp trực tiếp với {$name}. Trình bày roadmap sản phẩm và thảo luận yêu cầu tùy chỉnh.",
            ActivityType::Email => "Gửi email cho {$name}: tài liệu kỹ thuật, case study ngành và đề xuất báo giá sơ bộ.",
            ActivityType::Demo => "Demo sản phẩm 60 phút cho team của {$name} tại {$company}. Tập trung vào pipeline management và reporting.",
            default => "Hoạt động tương tác với {$name} từ {$company}.",
        };
    }
}
