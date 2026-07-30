<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\PriceBook;
use App\Models\User;
use App\Services\PriceBookService;
use App\Services\ProductCatalogService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductCatalogPriceBookTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $department = Department::factory()->create();
        $this->actor = User::factory()->create(['department_id' => $department->id]);
        $this->actor->assignRole('super-admin');
    }

    public function test_catalog_product_and_effective_price_book_entry_are_saved(): void
    {
        $product = app(ProductCatalogService::class)->save($this->actor, null, [
            'sku' => 'SF-PRO-01',
            'name' => 'Gói SalesFlow Pro',
            'description' => 'Gói CRM cho nhóm kinh doanh.',
            'unit' => 'Gói',
            'standard_price' => '500000000',
            'vat_percent' => '10',
            'is_active' => true,
        ]);

        $book = app(PriceBookService::class)->save($this->actor, null, [
            'name' => 'Bảng giá khách hàng doanh nghiệp',
            'customer_segment' => 'Enterprise',
            'region' => 'Toàn quốc',
            'currency_code' => 'VND',
            'effective_from' => now()->toDateString(),
            'effective_until' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        $entry = app(PriceBookService::class)->saveEntry($this->actor, $book, null, [
            'product_id' => $product->id,
            'unit_price' => '450000000',
            'vat_percent' => '8',
            'min_quantity' => 2,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'sku' => 'SF-PRO-01']);
        $this->assertDatabaseHas('price_book_entries', ['id' => $entry->id, 'price_book_id' => $book->id]);
        $this->assertTrue(PriceBook::query()->usable()->whereKey($book->id)->exists());
    }

    public function test_expired_price_book_is_not_usable(): void
    {
        $book = PriceBook::query()->create([
            'name' => 'Bảng giá cũ',
            'currency_code' => 'VND',
            'effective_until' => now()->subDay()->toDateString(),
            'is_active' => true,
            'owner_id' => $this->actor->id,
            'department_id' => $this->actor->department_id,
        ]);

        $this->assertFalse(PriceBook::query()->usable()->whereKey($book->id)->exists());
    }
}
