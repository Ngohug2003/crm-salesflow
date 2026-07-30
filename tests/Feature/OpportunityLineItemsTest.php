<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Opportunities\OpportunityLineItems as LineItemsComponent;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\PriceBook;
use App\Models\PriceBookEntry;
use App\Models\Product;
use App\Models\User;
use App\Services\OpportunityItemService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class OpportunityLineItemsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Department $dept;

    private Opportunity $opportunity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->dept = Department::factory()->create(['name' => 'Phòng Kinh doanh']);
        $this->superAdmin = User::factory()->create(['department_id' => $this->dept->id]);
        $this->superAdmin->assignRole('super-admin');

        $pipeline = Pipeline::query()->create(['name' => 'Default Pipeline', 'code' => 'default_items', 'is_default' => true]);
        $stage = PipelineStage::query()->create(['pipeline_id' => $pipeline->id, 'name' => 'Thương lượng', 'code' => 'negotiation_items', 'position' => 1]);

        $this->opportunity = Opportunity::factory()->create([
            'title' => 'Dự án mua sắm thiết bị IT',
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'amount' => 0,
            'owner_id' => $this->superAdmin->id,
        ]);
    }

    public function test_service_adds_line_item_and_recalculates_opportunity_amount(): void
    {
        $service = app(OpportunityItemService::class);

        $item = $service->addItem($this->superAdmin, $this->opportunity->id, [
            'product_name' => 'Máy chủ Dell PowerEdge',
            'sku' => 'DELL-PE-R750',
            'unit_price' => 50000000,
            'quantity' => 2,
            'discount_percent' => 10,
        ]);

        $this->assertEquals(90000000.0, (float) $item->total_price);

        $this->opportunity->refresh();
        $this->assertEquals(90000000.0, (float) $this->opportunity->amount);
    }

    public function test_livewire_component_renders_and_saves_line_item(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(LineItemsComponent::class, ['opportunityId' => $this->opportunity->id])
            ->assertOk()
            ->call('openCreate')
            ->set('productName', 'Phần mềm CRM Enterprise')
            ->set('unitPrice', '20000000')
            ->set('quantity', 3)
            ->set('discountPercent', '5')
            ->call('save')
            ->assertHasNoErrors();

        $this->opportunity->refresh();
        $this->assertEquals(57000000.0, (float) $this->opportunity->amount);
    }

    public function test_first_product_selection_uses_the_selected_price_book_price(): void
    {
        $product = Product::query()->create([
            'sku' => 'SF-ENTERPRISE',
            'name' => 'Gói SalesFlow Enterprise',
            'unit' => 'Gói',
            'standard_price' => 500000000,
            'vat_percent' => 10,
            'is_active' => true,
            'owner_id' => $this->superAdmin->id,
            'department_id' => $this->dept->id,
        ]);
        $priceBook = PriceBook::query()->create([
            'name' => 'Bảng giá Enterprise',
            'currency_code' => 'VND',
            'is_active' => true,
            'owner_id' => $this->superAdmin->id,
            'department_id' => $this->dept->id,
        ]);
        PriceBookEntry::query()->create([
            'price_book_id' => $priceBook->id,
            'product_id' => $product->id,
            'unit_price' => 450000000,
            'vat_percent' => 8,
            'min_quantity' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(LineItemsComponent::class, ['opportunityId' => $this->opportunity->id])
            ->call('openCreate')
            ->set('priceBookId', (string) $priceBook->id)
            ->set('productId', (string) $product->id)
            ->assertSet('unitPrice', '450000000.00')
            ->assertSet('vatPercent', '8.00')
            ->assertDispatched('salesflow-money-input-updated');
    }
}
