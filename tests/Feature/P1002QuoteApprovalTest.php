<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\QuoteApprovalStatus;
use App\Enums\QuoteStatus;
use App\Exceptions\QuoteWorkflowException;
use App\Jobs\RenderQuotePdfJob;
use App\Models\Company;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\OpportunityItem;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Quote;
use App\Models\QuoteDocument;
use App\Models\User;
use App\Services\QuoteApprovalService;
use App\Services\QuoteDocumentService;
use App\Services\QuoteService;
use App\Services\VietnameseMoneyService;
use Database\Seeders\QuoteApprovalSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

final class P1002QuoteApprovalTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $sales;

    private User $manager;

    private User $admin;

    private Opportunity $opportunity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, QuoteApprovalSeeder::class]);

        $this->department = Department::factory()->create(['name' => 'Kinh doanh B2B']);
        $this->sales = User::factory()->create([
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);
        $this->sales->assignRole('sales');
        $this->manager = User::factory()->create([
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);
        $this->manager->assignRole('sales-manager');
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');

        $company = Company::factory()->create([
            'name' => 'Công ty B2B Việt Nam',
            'owner_id' => $this->sales->id,
            'department_id' => $this->department->id,
        ]);
        $pipeline = Pipeline::factory()->create(['is_default' => true]);
        $stage = PipelineStage::factory()->create(['pipeline_id' => $pipeline->id]);
        $this->opportunity = Opportunity::factory()->create([
            'title' => 'Triển khai CRM doanh nghiệp',
            'company_id' => $company->id,
            'amount' => '100000000.00',
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'owner_id' => $this->sales->id,
            'department_id' => $this->department->id,
        ]);
        OpportunityItem::query()->create([
            'opportunity_id' => $this->opportunity->id,
            'product_name' => 'SalesFlow Enterprise',
            'quantity' => 1,
            'unit_price' => '100000000.00',
            'discount_percent' => '0.00',
            'total_price' => '100000000.00',
        ]);
    }

    public function test_decimal_totals_and_default_thresholds_are_enforced(): void
    {
        $quote = $this->createQuote('10000000.00');
        $request = app(QuoteApprovalService::class)->submit($this->sales, $quote->id);

        self::assertSame('10.00', $quote->refresh()->discount_percent);
        self::assertSame('99000000.00', $quote->total_amount);
        self::assertSame(QuoteApprovalStatus::Approved, $request->status);
        self::assertSame(QuoteStatus::Approved, $quote->status);

        $managerQuote = $this->createQuote('10010000.00');
        $managerRequest = app(QuoteApprovalService::class)->submit($this->sales, $managerQuote->id);

        self::assertSame(QuoteApprovalStatus::Pending, $managerRequest->status);
        self::assertSame('sales-manager', $managerRequest->required_role);
        self::assertSame(QuoteStatus::PendingApproval, $managerQuote->refresh()->status);

        $adminQuote = $this->createQuote('20010000.00');
        $adminRequest = app(QuoteApprovalService::class)->submit($this->sales, $adminQuote->id);
        self::assertSame('admin', $adminRequest->required_role);

        try {
            app(QuoteApprovalService::class)->approve($this->manager, $adminRequest->id);
            self::fail('Sales Manager không được duyệt chiết khấu trên 20%.');
        } catch (QuoteWorkflowException) {
            self::assertSame(QuoteStatus::PendingApproval, $adminQuote->refresh()->status);
        }

        app(QuoteApprovalService::class)->approve($this->admin, $adminRequest->id);
        self::assertSame(QuoteStatus::Approved, $adminQuote->refresh()->status);
    }

    public function test_creator_cannot_self_approve_and_manager_can_approve_department_quote(): void
    {
        $quote = $this->createQuote('15000000.00');
        $request = app(QuoteApprovalService::class)->submit($this->sales, $quote->id);

        try {
            app(QuoteApprovalService::class)->approve($this->sales, $request->id);
            self::fail('Người lập báo giá không được tự phê duyệt.');
        } catch (QuoteWorkflowException $exception) {
            self::assertSame('Người lập yêu cầu không được tự phê duyệt báo giá.', $exception->getMessage());
        }

        app(QuoteApprovalService::class)->approve($this->manager, $request->id, 'Đồng ý mức chiết khấu.');
        self::assertSame(QuoteStatus::Approved, $quote->refresh()->status);
    }

    public function test_manager_approval_then_issue_generates_one_private_pdf_on_retry(): void
    {
        Storage::fake('local');
        Queue::fake();

        $quote = $this->createQuote('15000000.00');
        $request = app(QuoteApprovalService::class)->submit($this->sales, $quote->id);
        app(QuoteApprovalService::class)->approve($this->manager, $request->id, 'Biên độ phù hợp.');
        $document = app(QuoteDocumentService::class)->issue($this->sales, $quote->id);

        Queue::assertPushed(RenderQuotePdfJob::class, 1);
        $job = new RenderQuotePdfJob($document->id);
        $job->handle(app(VietnameseMoneyService::class));
        $job->handle(app(VietnameseMoneyService::class));

        $document->refresh();
        self::assertSame('ready', $document->status);
        Storage::disk('local')->assertExists($document->path);
        self::assertSame(1, QuoteDocument::query()->where('quote_id', $quote->id)->count());
        self::assertSame(QuoteStatus::Issued, $quote->refresh()->status);
    }

    public function test_rejected_quote_can_be_resubmitted_as_a_new_version(): void
    {
        $quote = $this->createQuote('15000000.00');
        $firstRequest = app(QuoteApprovalService::class)->submit($this->sales, $quote->id);
        app(QuoteApprovalService::class)->reject($this->manager, $firstRequest->id, 'Cần làm rõ điều khoản.');

        self::assertSame(QuoteStatus::Rejected, $quote->refresh()->status);
        self::assertSame(1, $quote->version);

        $secondRequest = app(QuoteApprovalService::class)->submit($this->sales, $quote->id, 'Đã bổ sung giải trình.');

        self::assertSame(2, $quote->refresh()->version);
        self::assertSame(2, $secondRequest->quote_version);
        self::assertSame(QuoteApprovalStatus::Pending, $secondRequest->status);
        self::assertSame(2, $quote->approvalRequests()->count());
    }

    public function test_signed_pdf_download_rejects_tampered_url(): void
    {
        Storage::fake('local');
        Queue::fake();

        $quote = $this->createQuote('0');
        app(QuoteApprovalService::class)->submit($this->sales, $quote->id);
        $document = app(QuoteDocumentService::class)->issue($this->sales, $quote->id);
        Storage::disk('local')->put($document->path, '%PDF-test');
        $document->update(['status' => 'ready', 'size' => 9]);

        $url = URL::temporarySignedRoute('quotes.documents.download', now()->addMinutes(5), [
            'quoteId' => $quote->id,
            'documentId' => $document->id,
        ]);
        $this->actingAs($this->sales)->get($url)->assertOk();
        $this->actingAs($this->sales)->get($url.'&documentId=999999')->assertForbidden();
        $this->actingAs($this->sales)
            ->get(route('quotes.show', $quote->id))
            ->assertOk()
            ->assertSee('Tải PDF chính thức')
            ->assertDontSee('In Báo giá / Lưu PDF');
    }

    public function test_quote_manager_and_approval_inbox_render_with_permission_boundaries(): void
    {
        Queue::fake();
        $quote = $this->createQuote('15000000.00');
        $approval = app(QuoteApprovalService::class)->submit($this->sales, $quote->id);
        $this->createQuote('5000000.00');

        $issuedQuote = $this->createQuote('0.00');
        app(QuoteApprovalService::class)->submit($this->sales, $issuedQuote->id);
        app(QuoteDocumentService::class)->issue($this->sales, $issuedQuote->id);

        $this->actingAs($this->sales);
        Livewire::test('opportunities.quote-manager', ['opportunityId' => $this->opportunity->id])
            ->assertSee($quote->quote_number)
            ->assertSee('Chờ phê duyệt');
        $this->get(route('quotes.approvals'))->assertForbidden();

        $this->actingAs($this->manager)
            ->get(route('quotes.approvals'))
            ->assertOk()
            ->assertSee($quote->quote_number)
            ->assertSee('Phê duyệt');

        $this->actingAs($this->admin)
            ->get(route('quotes.settings'))
            ->assertOk()
            ->assertSee('Ma trận phê duyệt chiết khấu');

        $this->actingAs($this->admin);
        Livewire::test('opportunities.quote-manager', ['opportunityId' => $this->opportunity->id])
            ->assertSee('Phê duyệt')
            ->assertSee('Từ chối')
            ->assertDontSee('Gửi duyệt')
            ->assertDontSee('Đánh dấu đã gửi')
            ->call('openApprovalDecision', $approval->id, 'approve')
            ->assertSet('showDecisionModal', true)
            ->call('resolveApprovalDecision')
            ->assertSet('showDecisionModal', false)
            ->assertSee('Báo giá đã được phê duyệt.');

        self::assertSame(QuoteStatus::Approved, $quote->refresh()->status);
    }

    private function createQuote(string $discount): Quote
    {
        return app(QuoteService::class)->createFromOpportunity($this->sales, $this->opportunity->id, [
            'tax_percent' => '10.00',
            'discount_amount' => $discount,
        ]);
    }
}
