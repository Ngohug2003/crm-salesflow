<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Products\ProductDetail;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\User;
use App\Services\FurnitureCatalogService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class ProductMediaTest extends TestCase
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
        $this->product = Product::query()->create([
            'sku' => 'DESK-MEDIA-01',
            'name' => 'Bàn làm việc có thư viện ảnh',
            'unit' => 'Cái',
            'standard_price' => 3500000,
            'vat_percent' => 10,
            'commercial_status' => 'active',
            'is_active' => true,
            'owner_id' => $this->actor->id,
            'department_id' => $department->id,
        ]);
    }

    public function test_authorized_user_can_upload_multiple_product_images_from_livewire(): void
    {
        $this->actingAs($this->actor);

        Livewire::test(ProductDetail::class, ['productId' => $this->product->id])
            ->call('openMediaUploader')
            ->set('mediaUploads', [
                UploadedFile::fake()->image('ban-chinh.jpg', 1200, 900),
                UploadedFile::fake()->image('ban-chi-tiet.jpg', 800, 800),
            ])
            ->call('uploadMedia')
            ->assertHasNoErrors()
            ->assertSet('showMediaUploader', false);

        $media = ProductMedia::query()->where('product_id', $this->product->id)->orderBy('sort_order')->get();
        $this->assertCount(2, $media);
        $this->assertTrue($media->first()->is_primary);
        $this->assertFalse($media->last()->is_primary);
        Storage::disk('s3')->assertExists($media->first()->path);
        Storage::disk('s3')->assertExists($media->last()->path);
    }

    public function test_media_can_be_reordered_promoted_and_deleted_safely(): void
    {
        $service = app(FurnitureCatalogService::class);
        $media = $service->uploadMedia($this->actor, $this->product, [
            UploadedFile::fake()->image('goc-trai.jpg'),
            UploadedFile::fake()->image('goc-phai.jpg'),
        ]);
        $first = $media->first();
        $second = $media->last();

        $service->setPrimaryMedia($this->actor, $this->product, $second);
        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);

        $service->moveMedia($this->actor, $this->product, $second->fresh(), 'up');
        $this->assertLessThan($first->fresh()->sort_order, $second->fresh()->sort_order);

        $deletedPath = $second->path;
        $service->deleteMedia($this->actor, $this->product, $second->fresh());
        $this->assertDatabaseMissing('product_media', ['id' => $second->id]);
        $this->assertTrue($first->fresh()->is_primary);
        Storage::disk('s3')->assertMissing($deletedPath);
    }

    public function test_product_image_route_requires_visibility_and_streams_stored_image(): void
    {
        $media = app(FurnitureCatalogService::class)->uploadMedia($this->actor, $this->product, [
            UploadedFile::fake()->image('anh-chinh.png'),
        ])->first();

        $this->actingAs($this->actor)
            ->get(route('products.media.show', [$this->product->id, $media->id]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $unauthorized = User::factory()->create(['department_id' => $this->actor->department_id]);
        $this->actingAs($unauthorized)
            ->get(route('products.media.show', [$this->product->id, $media->id]))
            ->assertForbidden();
    }
}
