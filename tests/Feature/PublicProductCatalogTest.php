<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Client\Products\ProductDetail as ClientProductDetail;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\FurnitureCatalogService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class PublicProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        config()->set('filesystems.default', 's3');
        $this->seed(RolePermissionSeeder::class);
        $department = Department::factory()->create();
        $this->actor = User::factory()->create(['department_id' => $department->id]);
        $this->actor->assignRole('super-admin');
        $category = ProductCategory::query()->create([
            'code' => 'DESK',
            'name' => 'Bàn làm việc',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $this->product = Product::query()->create([
            'product_category_id' => $category->id,
            'sku' => 'DESK-PUBLIC-01',
            'name' => 'Bàn làm việc Public Demo',
            'unit' => 'Cái',
            'standard_price' => 5500000,
            'vat_percent' => 10,
            'warranty_months' => 24,
            'commercial_status' => 'active',
            'is_active' => true,
            'owner_id' => $this->actor->id,
            'department_id' => $department->id,
        ]);
        ProductVariant::query()->create([
            'product_id' => $this->product->id,
            'sku' => 'DESK-PUBLIC-01-1400',
            'name' => '1400 mm · Óc chó',
            'length_mm' => 1400,
            'width_mm' => 700,
            'height_mm' => 750,
            'material' => 'MDF lõi xanh',
            'color' => 'Óc chó',
            'finish' => 'Melamine',
            'unit' => 'Cái',
            'standard_price' => 5500000,
            'vat_percent' => 10,
            'warranty_months' => 24,
            'lead_time_days' => 14,
            'commercial_status' => 'active',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_public_catalog_lists_active_products_and_filters_by_search(): void
    {
        Product::query()->create([
            'sku' => 'OLD-PUBLIC-01',
            'name' => 'Sản phẩm ngừng kinh doanh',
            'unit' => 'Cái',
            'commercial_status' => 'discontinued',
            'is_active' => true,
            'owner_id' => $this->actor->id,
            'department_id' => $this->actor->department_id,
        ]);

        $this->get(route('client.products.index'))
            ->assertOk()
            ->assertSee('Bàn làm việc Public Demo')
            ->assertDontSee('Sản phẩm ngừng kinh doanh');

        $this->get(route('client.products.index', ['q' => 'DESK-PUBLIC-01']))
            ->assertOk()
            ->assertSee('Bàn làm việc Public Demo');
    }

    public function test_public_product_detail_shows_sellable_variant_but_not_supplier_data(): void
    {
        $this->get(route('client.products.show', $this->product->id))
            ->assertOk()
            ->assertSee('Bàn làm việc Public Demo')
            ->assertSee('1400 mm · Óc chó')
            ->assertSee('MDF lõi xanh')
            ->assertSee('Liên hệ tư vấn sản phẩm')
            ->assertDontSee('Nhà cung cấp')
            ->assertDontSee('Giá mua');
    }

    public function test_public_media_route_streams_image_without_authentication(): void
    {
        /** @var ProductMedia $media */
        $media = app(FurnitureCatalogService::class)->uploadMedia(
            $this->actor,
            $this->product,
            [UploadedFile::fake()->image('public-demo.jpg')],
        )->first();

        $this->get(route('client.products.media', [$this->product->id, $media->id]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=86400, public');
    }

    public function test_client_detail_can_switch_media_and_variant_without_page_reload(): void
    {
        $media = app(FurnitureCatalogService::class)->uploadMedia(
            $this->actor,
            $this->product,
            [
                UploadedFile::fake()->image('public-main.jpg'),
                UploadedFile::fake()->image('public-detail.jpg'),
            ],
        );
        /** @var ProductVariant $variant */
        $variant = $this->product->variants()->firstOrFail();

        Livewire::test(ClientProductDetail::class, ['productId' => $this->product->id])
            ->assertSet('selectedMediaId', $media->first()->id)
            ->call('selectMedia', $media->last()->id)
            ->assertSet('selectedMediaId', $media->last()->id)
            ->call('selectVariant', $variant->id)
            ->assertSet('selectedVariantId', $variant->id);
    }
}
