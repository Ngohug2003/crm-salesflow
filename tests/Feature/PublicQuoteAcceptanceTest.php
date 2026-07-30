<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Livewire\Public\PublicQuoteViewer;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteDocumentService;
use App\Services\QuotePublicLinkService;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

final class PublicQuoteAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private QuotePublicLinkService $linkService;

    private User $salesUser;

    private Quote $quote;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DepartmentSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $salesDept = Department::query()->where('code', 'SALES')->firstOrFail();

        $this->salesUser = User::factory()->create([
            'is_active' => true,
            'department_id' => $salesDept->id,
        ]);
        $this->salesUser->assignRole('sales');

        $pipeline = Pipeline::factory()->create();
        $stage = PipelineStage::factory()->create(['pipeline_id' => $pipeline->id]);

        $company = Company::factory()->create();
        $contact = Contact::factory()->create(['company_id' => $company->id]);
        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'owner_id' => $this->salesUser->id,
            'department_id' => $salesDept->id,
        ]);

        $this->quote = Quote::query()->create([
            'quote_number' => 'Q-TEST-1005',
            'opportunity_id' => $opportunity->id,
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'status' => QuoteStatus::Issued,
            'subtotal' => '10000000.00',
            'tax_percent' => '10.00',
            'tax_amount' => '1000000.00',
            'discount_amount' => '0.00',
            'total_amount' => '11000000.00',
            'version' => 1,
            'created_by' => $this->salesUser->id,
        ]);

        $quoteForSnapshot = Quote::query()
            ->with(['opportunity.department', 'company', 'contact', 'creator', 'items'])
            ->findOrFail($this->quote->id);
        $this->quote->update([
            'issued_snapshot' => app(QuoteDocumentService::class)->snapshot($quoteForSnapshot),
        ]);

        $this->linkService = app(QuotePublicLinkService::class);
    }

    public function test_sales_can_generate_public_quote_link(): void
    {
        $result = $this->linkService->generateLink($this->quote, $this->salesUser);

        self::assertNotNull($result['link']);
        self::assertSame(64, strlen($result['plain_token']));
        self::assertDatabaseHas('quote_public_links', [
            'id' => $result['link']->id,
            'quote_id' => $this->quote->id,
            'token_hash' => hash('sha256', $result['plain_token']),
            'revoked_at' => null,
        ]);
    }

    public function test_guest_can_view_quote_via_valid_public_token(): void
    {
        $result = $this->linkService->generateLink($this->quote, $this->salesUser);

        $response = $this->get(route('quotes.public-show', ['token' => $result['plain_token']]));

        $response->assertStatus(200);
        $response->assertSee($this->quote->quote_number);
        $response->assertSee($this->quote->company->name);

        self::assertDatabaseHas('quote_public_links', [
            'id' => $result['link']->id,
            'view_count' => 1,
        ]);
    }

    public function test_public_page_uses_the_issued_snapshot_not_live_quote_fields(): void
    {
        $result = $this->linkService->generateLink($this->quote, $this->salesUser);
        $snapshotNumber = $this->quote->quote_number;
        $this->quote->update(['quote_number' => 'Q-LIVE-CHANGED']);

        $this->get(route('quotes.public-show', ['token' => $result['plain_token']]))
            ->assertOk()
            ->assertSee($snapshotNumber)
            ->assertDontSee('Q-LIVE-CHANGED');
    }

    public function test_public_page_handles_missing_optional_snapshot_party_fields(): void
    {
        $result = $this->linkService->generateLink($this->quote, $this->salesUser);
        $link = $result['link'];
        $snapshot = $link->public_snapshot;
        $snapshot['company'] = [];
        $snapshot['contact'] = [];
        $snapshot['creator'] = [];
        $link->update(['public_snapshot' => $snapshot]);

        $this->get(route('quotes.public-show', ['token' => $result['plain_token']]))
            ->assertOk()
            ->assertSee('Khách hàng cá nhân');
    }

    public function test_invalid_or_expired_or_revoked_token_shows_invalid_view(): void
    {
        $response = $this->get(route('quotes.public-show', ['token' => 'invalid-token-string-12345']));

        $response->assertStatus(200);
        $response->assertSee('Đường dẫn không hợp lệ hoặc đã hết hạn');
    }

    public function test_customer_can_accept_quote_and_updates_quote_status(): void
    {
        $result = $this->linkService->generateLink($this->quote, $this->salesUser);

        Livewire::test(PublicQuoteViewer::class, ['token' => $result['plain_token']])
            ->assertStatus(200)
            ->call('openResponseModal', 'accept')
            ->set('signerName', 'Trần Văn Khách')
            ->set('signerEmail', 'khachhang@company.com')
            ->set('signerTitle', 'Giám đốc Mua hàng')
            ->set('feedbackNotes', 'Đồng ý với các điều khoản báo giá.')
            ->call('submitResponse')
            ->assertHasNoErrors();

        $this->quote->refresh();
        self::assertSame(QuoteStatus::Accepted, $this->quote->status);

        self::assertDatabaseHas('quote_customer_responses', [
            'quote_id' => $this->quote->id,
            'response_type' => 'accept',
            'signer_name' => 'Trần Văn Khách',
            'signer_email' => 'khachhang@company.com',
        ]);
    }

    public function test_customer_can_decline_quote_and_updates_quote_status(): void
    {
        $result = $this->linkService->generateLink($this->quote, $this->salesUser);

        Livewire::test(PublicQuoteViewer::class, ['token' => $result['plain_token']])
            ->assertStatus(200)
            ->call('openResponseModal', 'decline')
            ->set('signerName', 'Nguyễn Thị B')
            ->set('signerEmail', 'nguyenthib@company.com')
            ->set('signerTitle', 'Trưởng phòng IT')
            ->set('feedbackNotes', 'Giá cao hơn dự toán cho phép.')
            ->call('submitResponse')
            ->assertHasNoErrors();

        $this->quote->refresh();
        self::assertSame(QuoteStatus::Declined, $this->quote->status);
        self::assertSame('Giá cao hơn dự toán cho phép.', $this->quote->rejection_reason);

        self::assertDatabaseHas('quote_customer_responses', [
            'quote_id' => $this->quote->id,
            'response_type' => 'decline',
            'signer_name' => 'Nguyễn Thị B',
        ]);
    }

    public function test_customer_can_only_make_one_final_decision_but_comment_does_not_block_it(): void
    {
        $result = $this->linkService->generateLink($this->quote, $this->salesUser);
        $link = $result['link'];
        $customer = [
            'signer_name' => 'Khách hàng thử nghiệm',
            'signer_email' => 'customer@example.test',
            'feedback_notes' => 'Vui lòng giải thích thêm điều khoản thanh toán.',
        ];

        $this->linkService->recordResponse($link, 'comment', $customer);
        $this->linkService->recordResponse($link, 'accept', $customer);

        $this->expectException(ValidationException::class);
        $this->linkService->recordResponse($link, 'decline', $customer);
    }

    public function test_access_code_must_be_verified_before_rendering_quote_details(): void
    {
        $result = $this->linkService->generateLink($this->quote, $this->salesUser, null, 'SalesFlow-2026');

        Livewire::test(PublicQuoteViewer::class, ['token' => $result['plain_token']])
            ->assertSee('Nhập mã truy cập')
            ->assertDontSee($this->quote->quote_number)
            ->set('accessCode', 'sai-ma')
            ->call('submitAccessCode')
            ->assertHasErrors(['accessCode'])
            ->set('accessCode', 'SalesFlow-2026')
            ->call('submitAccessCode')
            ->assertHasNoErrors()
            ->assertSee($this->quote->quote_number);
    }

    public function test_public_link_cannot_be_generated_for_quote_without_issued_snapshot(): void
    {
        $this->quote->update(['issued_snapshot' => null]);

        $this->expectException(ValidationException::class);
        $this->linkService->generateLink($this->quote->fresh(), $this->salesUser);
    }

    public function test_pdf_can_be_issued_and_reissued_for_accepted_quote(): void
    {
        $this->quote->update(['status' => QuoteStatus::Accepted]);

        $docService = app(QuoteDocumentService::class);
        $document = $docService->issue($this->salesUser, $this->quote->id);

        self::assertNotNull($document);
        $this->quote->refresh();
        self::assertSame(QuoteStatus::Accepted, $this->quote->status);
    }

    public function test_guest_can_download_public_pdf_via_token(): void
    {
        $result = $this->linkService->generateLink($this->quote, $this->salesUser);

        $response = $this->get(route('quotes.public-pdf', ['token' => $result['plain_token']]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        self::assertDatabaseHas('quote_public_events', [
            'quote_public_link_id' => $result['link']->id,
            'event_type' => 'pdf_downloaded',
        ]);
    }
}
