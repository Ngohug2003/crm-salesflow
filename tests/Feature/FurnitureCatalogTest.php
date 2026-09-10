<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\PriceBook;
use App\Models\PriceBookEntry;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductSupplier;
use App\Models\User;
use App\Services\FurnitureCatalogService;
use App\Services\ProductCatalogService;
use Database\Seeders\FurnitureCatalogSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FurnitureCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $department = Department::factory()->create();
        $this->actor = User::factory()->create([
            'department_id' => $department->id,
            'email' => 'admin@salesflow.test',
        ]);
        $this->actor->assignRole('super-admin');
    }

    public function test_admin_can_build_furniture_product_with_variants_and_supplier_offer(): void
    {
        $catalog = app(FurnitureCatalogService::class);
        $category = $catalog->saveCategory($this->actor, null, [
            'code' => 'DESK',
            'name' => 'Bàn làm việc',
            'parent_id' => null,
            'sort_order' => 10,
            'is_active' => true,
        ]);
        $supplier = $catalog->saveSupplier($this->actor, null, [
            'code' => 'NCC-01',
            'name' => 'Xưởng nội thất A',
            'tax_code' => '0101234567',
            'contact_name' => 'Nguyễn An',
            'email' => 'an@example.test',
            'phone' => '0901000001',
            'address' => 'Hà Nội',
            'is_active' => true,
        ]);
        $product = app(ProductCatalogService::class)->save($this->actor, null, [
            'product_category_id' => $category->id,
            'sku' => 'DESK-ATLAS',
            'name' => 'Bàn làm việc Atlas',
            'description' => 'Bàn làm việc chân sắt.',
            'unit' => 'Cái',
            'standard_price' => '3500000',
            'vat_percent' => '10',
            'warranty_months' => 24,
            'commercial_status' => 'active',
            'specifications' => ['usage_area' => 'Khu nhân viên'],
            'is_active' => true,
        ]);

        $firstVariant = $catalog->saveVariant($this->actor, $product, null, $this->variantData(
            'DESK-ATLAS-1400-WAL',
            '3500000',
            true,
        ));
        $secondVariant = $catalog->saveVariant($this->actor, $product, null, $this->variantData(
            'DESK-ATLAS-1600-OAK',
            '4200000',
            true,
        ));
        $catalog->saveSupplierOffer($this->actor, $product, $secondVariant, $supplier, [
            'supplier_sku' => 'NCC-ATLAS-1600',
            'purchase_price' => '3100000',
            'lead_time_days' => 14,
            'is_preferred' => true,
        ]);

        $this->assertFalse($firstVariant->fresh()->is_default);
        $this->assertTrue($secondVariant->fresh()->is_default);
        $this->assertDatabaseHas('product_supplier_variant', [
            'product_supplier_id' => $supplier->id,
            'product_variant_id' => $secondVariant->id,
            'supplier_sku' => 'NCC-ATLAS-1600',
            'is_preferred' => true,
        ]);
    }

    public function test_catalog_pages_are_available_to_authorized_user(): void
    {
        $product = Product::query()->create([
            'sku' => 'CHAIR-ERGO',
            'name' => 'Ghế công thái học',
            'unit' => 'Cái',
            'standard_price' => 2800000,
            'vat_percent' => 10,
            'commercial_status' => 'active',
            'is_active' => true,
            'owner_id' => $this->actor->id,
            'department_id' => $this->actor->department_id,
        ]);
        ProductSupplier::query()->create([
            'code' => 'NCC-GHE',
            'name' => 'Nhà cung cấp ghế',
            'is_active' => true,
        ]);

        $this->actingAs($this->actor)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Danh mục nội thất văn phòng');
        $this->actingAs($this->actor)
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Biến thể bán hàng');
        $this->actingAs($this->actor)
            ->get(route('products.catalog-settings'))
            ->assertOk()
            ->assertSee('Danh mục')
            ->assertSee('Nhà cung cấp');
    }

    public function test_user_without_product_permission_cannot_open_catalog_settings(): void
    {
        $user = User::factory()->create(['department_id' => $this->actor->department_id]);

        $this->actingAs($user)
            ->get(route('products.catalog-settings'))
            ->assertForbidden();
    }

    public function test_furniture_seeder_removes_legacy_computer_catalog_and_empty_price_books(): void
    {
        $category = ProductCategory::query()->create([
            'code' => 'LAPTOP',
            'name' => 'Laptop',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $brand = ProductBrand::query()->create([
            'code' => 'APPLE',
            'name' => 'Apple',
            'is_active' => true,
        ]);
        $legacyProduct = Product::query()->create([
            'product_category_id' => $category->id,
            'product_brand_id' => $brand->id,
            'sku' => 'APP-MBA-M3-16-512',
            'name' => 'MacBook Air M3',
            'unit' => 'Cái',
            'standard_price' => 30000000,
            'vat_percent' => 10,
            'commercial_status' => 'active',
            'is_active' => true,
            'owner_id' => $this->actor->id,
            'department_id' => $this->actor->department_id,
        ]);
        $priceBook = PriceBook::query()->create([
            'name' => 'Bảng giá máy tính cũ',
            'currency_code' => 'VND',
            'is_active' => true,
            'owner_id' => $this->actor->id,
            'department_id' => $this->actor->department_id,
        ]);
        PriceBookEntry::query()->create([
            'price_book_id' => $priceBook->id,
            'product_id' => $legacyProduct->id,
            'unit_price' => 29000000,
            'vat_percent' => 10,
            'min_quantity' => 1,
            'is_active' => true,
        ]);

        $this->seed(FurnitureCatalogSeeder::class);

        $this->assertDatabaseMissing('products', ['sku' => 'APP-MBA-M3-16-512']);
        $this->assertDatabaseMissing('product_categories', ['code' => 'LAPTOP']);
        $this->assertDatabaseMissing('product_brands', ['code' => 'APPLE']);
        $this->assertDatabaseMissing('price_books', ['id' => $priceBook->id]);
        $this->assertDatabaseHas('products', ['sku' => 'DESK-ATLAS']);
        $this->assertDatabaseHas('product_categories', ['code' => 'ACCESSORY', 'name' => 'Phụ kiện nội thất']);
    }

    /** @return array<string, mixed> */
    private function variantData(string $sku, string $price, bool $isDefault): array
    {
        return [
            'sku' => $sku,
            'name' => 'Biến thể '.$sku,
            'length_mm' => 1400,
            'width_mm' => 700,
            'height_mm' => 750,
            'material' => 'MDF lõi xanh và thép',
            'color' => 'Óc chó',
            'finish' => 'Melamine',
            'unit' => 'Cái',
            'standard_price' => $price,
            'vat_percent' => '10',
            'warranty_months' => 24,
            'lead_time_days' => 12,
            'commercial_status' => 'made_to_order',
            'is_default' => $isDefault,
            'is_active' => true,
        ];
    }
}
