<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\OpportunityItem;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class QuoteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    private Company $company;

    private Contact $contact;

    private Opportunity $opportunity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->department = Department::factory()->create([
            'name' => 'Phòng Phần mềm Doanh nghiệp',
            'code' => 'SOFTWARE_DEP',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Lê Quốc Bảo',
            'email' => 'bao.le@salesflow.test',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);
        $this->user->assignRole('sales');

        $this->company = Company::factory()->create([
            'name' => 'Công ty Cổ phần Công nghệ Ánh Dương',
            'tax_code' => '0312345601',
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $this->contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'full_name' => 'Nguyễn Văn Anh',
            'email' => 'anh.nguyen@anhduong.test',
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $pipeline = Pipeline::factory()->create(['is_default' => true]);
        $stage = PipelineStage::factory()->create(['pipeline_id' => $pipeline->id]);

        $this->opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'contact_id' => $this->contact->id,
            'title' => 'Dự án Hợp đồng Phần mềm CRM 2026',
            'amount' => 100000000,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        OpportunityItem::query()->create([
            'opportunity_id' => $this->opportunity->id,
            'product_name' => 'Gói CRM Enterprise 20 User',
            'sku' => 'CRM-ENT-20',
            'unit_price' => 5000000,
            'quantity' => 20,
            'discount_percent' => 10,
            'total_price' => 90000000, // 20 * 5m * 0.9 = 90m
        ]);
    }

    public function test_service_creates_quote_from_opportunity_with_calculated_amounts(): void
    {
        $service = app(QuoteService::class);
        $quote = $service->createFromOpportunity($this->user, $this->opportunity->id, [
            'valid_until' => '2026-12-31',
            'tax_percent' => 10,
            'discount_amount' => 5000000,
            'notes' => 'Thanh toán 50% sau khi ký hợp đồng',
        ]);

        $this->assertStringStartsWith('BG-', $quote->quote_number);
        $this->assertEquals(QuoteStatus::Draft, $quote->status);
        $this->assertEquals(90000000, (float) $quote->subtotal);
        $this->assertEquals(5000000, (float) $quote->discount_amount);
        $this->assertEquals(8500000, (float) $quote->tax_amount); // (90m - 5m) * 10% = 8.5m
        $this->assertEquals(93500000, (float) $quote->total_amount); // 85m + 8.5m = 93.5m
        $this->assertCount(1, $quote->items);
    }

    public function test_service_can_update_quote_status(): void
    {
        $service = app(QuoteService::class);
        $quote = $service->createFromOpportunity($this->user, $this->opportunity->id);

        $updatedQuote = $service->updateQuoteStatus($this->user, $quote, QuoteStatus::Sent);
        $this->assertEquals(QuoteStatus::Sent, $updatedQuote->status);

        $acceptedQuote = $service->updateQuoteStatus($this->user, $quote, QuoteStatus::Accepted);
        $this->assertEquals(QuoteStatus::Accepted, $acceptedQuote->status);
    }

    public function test_quote_manager_livewire_component_creates_and_manages_quotes(): void
    {
        $this->actingAs($this->user);

        Livewire::test('opportunities.quote-manager', ['opportunityId' => $this->opportunity->id])
            ->call('openCreateModal')
            ->set('validUntil', '2026-12-31')
            ->set('taxPercent', 10)
            ->set('discountAmount', 0)
            ->set('notes', 'Ghi chú chạy thử test')
            ->call('createQuote')
            ->assertSee('Đã khởi tạo thành công Báo giá mã');

        $quote = Quote::query()->where('opportunity_id', $this->opportunity->id)->firstOrFail();

        Livewire::test('opportunities.quote-manager', ['opportunityId' => $this->opportunity->id])
            ->assertSee($quote->quote_number)
            ->call('changeStatus', $quote->id, 'sent')
            ->assertSee('Đã gửi');
    }

    public function test_authorized_user_can_view_printable_quote(): void
    {
        $service = app(QuoteService::class);
        $quote = $service->createFromOpportunity($this->user, $this->opportunity->id);

        $response = $this->actingAs($this->user)->get(route('quotes.show', $quote->id));
        $response->assertStatus(200);
        $response->assertSee($quote->quote_number);
        $response->assertSee('BẢNG BÁO GIÁ');
        $response->assertSee('Gói CRM Enterprise 20 User');
    }
}
