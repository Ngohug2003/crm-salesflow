<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PriceBook;
use App\Models\PriceBookEntry;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductSupplier;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class FurnitureCatalogSeeder extends Seeder
{
    /** @var list<string> */
    private const LEGACY_PRODUCT_SKUS = [
        'SF-ENTERPRISE',
        'APP-MBA-M3-16-512',
        'DELL-LAT-5440-I7',
    ];

    /** @var list<string> */
    private const LEGACY_CATEGORY_CODES = [
        'LAPTOP',
        'MACBOOK',
        'DESKTOP',
        'MONITOR',
    ];

    /** @var list<string> */
    private const LEGACY_BRAND_CODES = [
        'APPLE',
        'DELL',
        'LENOVO',
        'HP',
        'ASUS',
    ];

    public function run(): void
    {
        $this->removeLegacyCatalogSeed();

        foreach ($this->categories() as $category) {
            ProductCategory::query()->updateOrCreate(['code' => $category['code']], $category);
        }
        foreach ($this->brands() as $brand) {
            ProductBrand::query()->updateOrCreate(['code' => $brand['code']], $brand);
        }
        foreach ($this->suppliers() as $supplier) {
            ProductSupplier::query()->updateOrCreate(['code' => $supplier['code']], $supplier);
        }

        $owner = User::query()->where('email', 'admin@salesflow.test')->first();
        if ($owner === null) {
            return;
        }

        foreach ($this->products() as $definition) {
            $product = Product::query()->updateOrCreate(['sku' => $definition['sku']], [
                'product_category_id' => ProductCategory::query()->where('code', $definition['category'])->value('id'),
                'product_brand_id' => ProductBrand::query()->where('code', $definition['brand'])->value('id'),
                'name' => $definition['name'],
                'model' => $definition['model'],
                'description' => $definition['description'],
                'unit' => $definition['unit'],
                'standard_price' => $definition['base_price'],
                'vat_percent' => 10,
                'warranty_months' => $definition['warranty_months'],
                'commercial_status' => 'active',
                'specifications' => ['usage_area' => $definition['usage_area']],
                'is_active' => true,
                'owner_id' => $owner->id,
                'department_id' => $owner->department_id,
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ]);

            foreach ($definition['variants'] as $index => $variantData) {
                $variant = ProductVariant::query()->updateOrCreate(['sku' => $variantData['sku']], [
                    'product_id' => $product->id,
                    'name' => $variantData['name'],
                    'length_mm' => $variantData['length_mm'],
                    'width_mm' => $variantData['width_mm'],
                    'height_mm' => $variantData['height_mm'],
                    'material' => $variantData['material'],
                    'color' => $variantData['color'],
                    'finish' => $variantData['finish'],
                    'unit' => $definition['unit'],
                    'standard_price' => $variantData['price'],
                    'vat_percent' => 10,
                    'warranty_months' => $definition['warranty_months'],
                    'lead_time_days' => $variantData['lead_time_days'],
                    'commercial_status' => $variantData['status'],
                    'is_default' => $index === 0,
                    'is_active' => true,
                ]);

                $supplier = ProductSupplier::query()->where('code', $variantData['supplier'])->firstOrFail();
                DB::table('product_supplier_variant')->updateOrInsert(
                    ['product_supplier_id' => $supplier->id, 'product_variant_id' => $variant->id],
                    [
                        'supplier_sku' => $variantData['supplier_sku'],
                        'purchase_price' => $variantData['purchase_price'],
                        'lead_time_days' => $variantData['lead_time_days'],
                        'is_preferred' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        $this->seedPriceBooks($owner);
    }

    private function removeLegacyCatalogSeed(): void
    {
        DB::transaction(function (): void {
            $legacyProductIds = Product::query()
                ->withTrashed()
                ->whereIn('sku', self::LEGACY_PRODUCT_SKUS)
                ->pluck('id');

            if ($legacyProductIds->isNotEmpty()) {
                $legacyPriceBookIds = DB::table('price_book_entries')
                    ->whereIn('product_id', $legacyProductIds)
                    ->distinct()
                    ->pluck('price_book_id');

                DB::table('price_book_entries')
                    ->whereIn('product_id', $legacyProductIds)
                    ->delete();

                Product::query()
                    ->withTrashed()
                    ->whereIn('id', $legacyProductIds)
                    ->forceDelete();

                DB::table('price_books')
                    ->whereIn('id', $legacyPriceBookIds)
                    ->whereNotExists(
                        fn ($query) => $query
                            ->selectRaw('1')
                            ->from('price_book_entries')
                            ->whereColumn('price_book_entries.price_book_id', 'price_books.id'),
                    )
                    ->delete();
            }

            ProductCategory::query()
                ->whereIn('code', self::LEGACY_CATEGORY_CODES)
                ->whereDoesntHave('products')
                ->delete();

            ProductBrand::query()
                ->whereIn('code', self::LEGACY_BRAND_CODES)
                ->whereDoesntHave('products')
                ->delete();
        });
    }

    /** @return list<array{code: string, name: string, sort_order: int, is_active: bool}> */
    private function categories(): array
    {
        return [
            ['code' => 'DESK', 'name' => 'Bàn làm việc', 'sort_order' => 10, 'is_active' => true],
            ['code' => 'CHAIR', 'name' => 'Ghế văn phòng', 'sort_order' => 20, 'is_active' => true],
            ['code' => 'MEETING', 'name' => 'Bàn ghế phòng họp', 'sort_order' => 30, 'is_active' => true],
            ['code' => 'STORAGE', 'name' => 'Tủ và lưu trữ', 'sort_order' => 40, 'is_active' => true],
            ['code' => 'PARTITION', 'name' => 'Vách ngăn', 'sort_order' => 50, 'is_active' => true],
            ['code' => 'SOFA', 'name' => 'Sofa và tiếp khách', 'sort_order' => 60, 'is_active' => true],
            ['code' => 'ACCESSORY', 'name' => 'Phụ kiện nội thất', 'sort_order' => 70, 'is_active' => true],
            ['code' => 'SERVICE', 'name' => 'Dịch vụ thiết kế và lắp đặt', 'sort_order' => 80, 'is_active' => true],
        ];
    }

    /** @return list<array{code: string, name: string, is_active: bool}> */
    private function brands(): array
    {
        return [
            ['code' => 'HOAPHAT', 'name' => 'Hòa Phát', 'is_active' => true],
            ['code' => 'XUANHOA', 'name' => 'Xuân Hòa', 'is_active' => true],
            ['code' => 'NOITHAT190', 'name' => 'Nội thất 190', 'is_active' => true],
            ['code' => 'SALESFLOW-CUSTOM', 'name' => 'Thiết kế theo dự án', 'is_active' => true],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function suppliers(): array
    {
        return [
            ['code' => 'NCC-HP', 'name' => 'Nội thất Hòa Phát', 'tax_code' => '0101234501', 'contact_name' => 'Nguyễn Minh An', 'email' => 'b2b@hoaphat-demo.test', 'phone' => '0901000001', 'address' => 'Hà Nội', 'is_active' => true],
            ['code' => 'NCC-XH', 'name' => 'Nội thất Xuân Hòa', 'tax_code' => '0101234502', 'contact_name' => 'Trần Thu Hà', 'email' => 'sales@xuanhoa-demo.test', 'phone' => '0901000002', 'address' => 'Hà Nội', 'is_active' => true],
            ['code' => 'NCC-190', 'name' => 'Nội thất 190', 'tax_code' => '0101234504', 'contact_name' => 'Phạm Đức Minh', 'email' => 'project@190-demo.test', 'phone' => '0901000004', 'address' => 'Hà Nội', 'is_active' => true],
            ['code' => 'NCC-SF', 'name' => 'Xưởng nội thất dự án SalesFlow', 'tax_code' => '0101234503', 'contact_name' => 'Lê Hoàng Nam', 'email' => 'xuong@salesflow.test', 'phone' => '0901000003', 'address' => 'Hưng Yên', 'is_active' => true],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function products(): array
    {
        return [
            $this->product('DESK-ATLAS', 'Bàn làm việc Atlas', 'Atlas', 'DESK', 'SALESFLOW-CUSTOM', 'Bàn chân sắt cho khu nhân viên, nhiều kích thước và màu hoàn thiện.', 3500000, 24, 'Khu làm việc nhân viên', [
                $this->variant('DESK-ATLAS-1400-WAL', '1400 × 700 mm · Óc chó', [1400, 700, 750], 'MDF lõi xanh, chân thép', 'Óc chó', 'Melamine', 3500000, 12, 'NCC-SF', 'SF-AT140-WAL', 2550000, 'made_to_order'),
                $this->variant('DESK-ATLAS-1600-OAK', '1600 × 800 mm · Sồi sáng', [1600, 800, 750], 'MDF lõi xanh, chân thép', 'Sồi sáng', 'Melamine', 4200000, 14, 'NCC-SF', 'SF-AT160-OAK', 3100000, 'made_to_order'),
            ]),
            $this->product('CHAIR-ERGO-E1', 'Ghế công thái học Ergo E1', 'Ergo E1', 'CHAIR', 'NOITHAT190', 'Ghế lưới công thái học có tựa đầu, tay 3D và hỗ trợ thắt lưng.', 2800000, 12, 'Khu nhân viên và quản lý', [
                $this->variant('CHAIR-ERGO-E1-BLK', 'Lưới đen', [660, 650, 1180], 'Khung nhựa kỹ thuật, lưới chịu lực', 'Đen', 'Lưới', 2800000, 5, 'NCC-190', '190-E1-BLK', 2150000),
            ]),
            $this->product('DESK-NOVA', 'Bàn nhân viên Nova', 'Nova', 'DESK', 'XUANHOA', 'Bàn gỗ công nghiệp tối giản cho văn phòng hiện đại.', 2600000, 12, 'Khu làm việc nhân viên', [
                $this->variant('DESK-NOVA-1200-OAK', '1200 × 600 mm · Sồi sáng', [1200, 600, 750], 'MFC chống ẩm', 'Sồi sáng', 'Melamine', 2600000, 7, 'NCC-XH', 'XH-NV120', 1950000),
                $this->variant('DESK-NOVA-1400-WHT', '1400 × 700 mm · Trắng', [1400, 700, 750], 'MFC chống ẩm', 'Trắng', 'Melamine', 3100000, 7, 'NCC-XH', 'XH-NV140', 2320000),
            ]),
            $this->product('DESK-PRESIDENT-P1', 'Bàn giám đốc President P1', 'President P1', 'DESK', 'HOAPHAT', 'Bàn giám đốc có tủ phụ, thiết kế trang trọng.', 12800000, 24, 'Phòng lãnh đạo', [
                $this->variant('DESK-PRESIDENT-P1-2000', '2000 × 1800 mm · Nâu trầm', [2000, 1800, 760], 'MDF veneer', 'Nâu trầm', 'Sơn PU', 12800000, 10, 'NCC-HP', 'HP-P1-2000', 9800000),
            ]),
            $this->product('DESK-FLEX-BENCH', 'Cụm bàn làm việc Flex Bench', 'Flex Bench', 'DESK', 'SALESFLOW-CUSTOM', 'Cụm bàn module cho nhóm 4–6 nhân viên, hỗ trợ máng điện.', 16800000, 24, 'Không gian làm việc mở', [
                $this->variant('DESK-FLEX-BENCH-4', 'Cụm 4 chỗ', [2400, 1200, 750], 'MDF lõi xanh, khung thép', 'Sồi sáng', 'Melamine', 16800000, 15, 'NCC-SF', 'SF-FLEX-4', 12600000, 'made_to_order'),
                $this->variant('DESK-FLEX-BENCH-6', 'Cụm 6 chỗ', [3600, 1200, 750], 'MDF lõi xanh, khung thép', 'Sồi sáng', 'Melamine', 23800000, 18, 'NCC-SF', 'SF-FLEX-6', 17800000, 'made_to_order'),
            ]),
            $this->product('DESK-LIFTUP', 'Bàn nâng hạ LiftUp', 'LiftUp', 'DESK', 'NOITHAT190', 'Bàn nâng hạ điện hai động cơ, ghi nhớ độ cao.', 9800000, 36, 'Nhân viên và quản lý', [
                $this->variant('DESK-LIFTUP-1400', '1400 × 700 mm · Trắng', [1400, 700, 650], 'MDF, khung thép nâng điện', 'Trắng', 'Laminate', 9800000, 7, 'NCC-190', '190-LIFT-140', 7600000),
            ]),
            $this->product('DESK-WELCOME', 'Quầy lễ tân Welcome', 'Welcome', 'DESK', 'SALESFLOW-CUSTOM', 'Quầy lễ tân chữ I có khoang kỹ thuật và đèn LED.', 18500000, 24, 'Sảnh lễ tân', [
                $this->variant('DESK-WELCOME-2400', '2400 mm · Trắng vân đá', [2400, 700, 1100], 'MDF chống ẩm', 'Trắng vân đá', 'Laminate', 18500000, 20, 'NCC-SF', 'SF-WC240', 13900000, 'made_to_order'),
            ]),
            $this->product('CHAIR-STAFF-M1', 'Ghế nhân viên Staff M1', 'Staff M1', 'CHAIR', 'XUANHOA', 'Ghế xoay lưới lưng trung, phù hợp trang bị số lượng lớn.', 1450000, 12, 'Khu làm việc nhân viên', [
                $this->variant('CHAIR-STAFF-M1-BLK', 'Lưới đen', [600, 600, 980], 'Khung nhựa, lưới', 'Đen', 'Lưới', 1450000, 3, 'NCC-XH', 'XH-M1-BLK', 1080000),
            ]),
            $this->product('CHAIR-LUX-M2', 'Ghế quản lý Lux M2', 'Lux M2', 'CHAIR', 'HOAPHAT', 'Ghế quản lý lưng cao bọc da công nghiệp.', 5200000, 24, 'Phòng quản lý', [
                $this->variant('CHAIR-LUX-M2-BRN', 'Da nâu', [680, 720, 1200], 'Khung thép, da PU', 'Nâu', 'Da PU', 5200000, 5, 'NCC-HP', 'HP-LUX-M2', 3980000),
            ]),
            $this->product('CHAIR-NEST', 'Ghế phòng họp Nest', 'Nest', 'CHAIR', 'NOITHAT190', 'Ghế chân quỳ lưới gọn nhẹ cho phòng họp.', 1750000, 12, 'Phòng họp', [
                $this->variant('CHAIR-NEST-GRY', 'Lưới xám', [560, 590, 900], 'Khung thép mạ, lưới', 'Xám', 'Lưới', 1750000, 4, 'NCC-190', '190-NEST-GRY', 1320000),
            ]),
            $this->product('CHAIR-GRACE', 'Ghế khách Grace', 'Grace', 'CHAIR', 'XUANHOA', 'Ghế tiếp khách chân tĩnh, đệm nỉ.', 980000, 12, 'Khu tiếp khách', [
                $this->variant('CHAIR-GRACE-BLU', 'Nỉ xanh navy', [520, 550, 830], 'Khung thép, đệm nỉ', 'Xanh navy', 'Nỉ', 980000, 3, 'NCC-XH', 'XH-GRACE-BLU', 720000),
            ]),
            $this->product('CHAIR-PANTRY-P1', 'Ghế bar Pantry P1', 'Pantry P1', 'CHAIR', 'HOAPHAT', 'Ghế bar điều chỉnh độ cao dùng tại pantry.', 1250000, 12, 'Pantry', [
                $this->variant('CHAIR-PANTRY-P1-BLK', 'Đen', [460, 480, 900], 'Khung thép mạ, nhựa PP', 'Đen', 'Nhám', 1250000, 4, 'NCC-HP', 'HP-PANTRY-P1', 910000),
            ]),
            $this->product('MEETING-SUMMIT', 'Bàn họp Summit', 'Summit', 'MEETING', 'SALESFLOW-CUSTOM', 'Bàn họp lớn tích hợp hộp điện âm bàn.', 24500000, 24, 'Phòng họp lớn', [
                $this->variant('MEETING-SUMMIT-3200', '3200 × 1200 mm · 10 chỗ', [3200, 1200, 750], 'MDF veneer, khung thép', 'Óc chó', 'Sơn PU', 24500000, 18, 'NCC-SF', 'SF-SUMMIT-32', 18200000, 'made_to_order'),
                $this->variant('MEETING-SUMMIT-4800', '4800 × 1600 mm · 16 chỗ', [4800, 1600, 750], 'MDF veneer, khung thép', 'Óc chó', 'Sơn PU', 36800000, 22, 'NCC-SF', 'SF-SUMMIT-48', 27400000, 'made_to_order'),
            ]),
            $this->product('MEETING-CONNECT', 'Bàn họp Connect', 'Connect', 'MEETING', 'HOAPHAT', 'Bàn họp module dành cho phòng họp vừa.', 9800000, 24, 'Phòng họp 6–8 người', [
                $this->variant('MEETING-CONNECT-2400', '2400 × 1100 mm · 8 chỗ', [2400, 1100, 750], 'MFC, chân thép', 'Sồi sáng', 'Melamine', 9800000, 8, 'NCC-HP', 'HP-CONNECT-24', 7450000),
            ]),
            $this->product('STORAGE-STEEL-S3', 'Tủ hồ sơ sắt Steel S3', 'Steel S3', 'STORAGE', 'HOAPHAT', 'Tủ sắt ba khoang cửa kính, khóa riêng.', 4650000, 24, 'Kho hồ sơ', [
                $this->variant('STORAGE-STEEL-S3-GRY', 'Sơn ghi sáng', [1350, 450, 1830], 'Thép sơn tĩnh điện', 'Ghi sáng', 'Sơn tĩnh điện', 4650000, 4, 'NCC-HP', 'HP-S3-GRY', 3560000),
            ]),
            $this->product('STORAGE-HORIZON', 'Tủ tài liệu Horizon', 'Horizon', 'STORAGE', 'XUANHOA', 'Tủ gỗ hai cánh trên kính, dưới kín.', 5900000, 24, 'Phòng quản lý', [
                $this->variant('STORAGE-HORIZON-WAL', 'Óc chó', [1200, 400, 2000], 'MDF chống ẩm', 'Óc chó', 'Melamine', 5900000, 8, 'NCC-XH', 'XH-HORIZON-WAL', 4380000),
            ]),
            $this->product('PARTITION-ACOUSTIC', 'Vách ngăn tiêu âm Acoustic', 'Acoustic', 'PARTITION', 'SALESFLOW-CUSTOM', 'Vách nỉ tiêu âm module cho bàn làm việc.', 1850000, 12, 'Không gian làm việc mở', [
                $this->variant('PARTITION-ACOUSTIC-1200', '1200 × 400 mm · Xám', [1200, 40, 400], 'Khung gỗ, bông tiêu âm', 'Xám', 'Nỉ', 1850000, 12, 'NCC-SF', 'SF-ACO-120', 1320000, 'made_to_order'),
            ]),
            $this->product('PARTITION-GLASS', 'Vách kính văn phòng ClearWall', 'ClearWall', 'PARTITION', 'SALESFLOW-CUSTOM', 'Vách kính cường lực khung nhôm cho phòng họp và phòng quản lý.', 2850000, 24, 'Phòng họp và phòng quản lý', [
                $this->variant('PARTITION-GLASS-10MM', 'Kính 10 mm · Khung đen', [1000, 10, 2800], 'Kính cường lực, nhôm', 'Trong suốt', 'Khung sơn đen', 2850000, 20, 'NCC-SF', 'SF-GLASS-10', 2050000, 'made_to_order'),
            ], 'm²'),
            $this->product('SOFA-LOUNGE-S2', 'Sofa văn phòng Lounge S2', 'Lounge S2', 'SOFA', 'NOITHAT190', 'Sofa hai chỗ phong cách hiện đại cho sảnh chờ.', 8900000, 24, 'Sảnh và khu tiếp khách', [
                $this->variant('SOFA-LOUNGE-S2-GRY', 'Nỉ xám · 2 chỗ', [1600, 760, 780], 'Khung gỗ, mút D40', 'Xám', 'Nỉ', 8900000, 10, 'NCC-190', '190-LS2-GRY', 6750000),
            ]),
            $this->product('ACCESSORY-PEDESTAL', 'Hộc tủ di động Mobile Pedestal', 'Pedestal', 'ACCESSORY', 'HOAPHAT', 'Hộc ba ngăn có khóa và bánh xe.', 1650000, 12, 'Dưới bàn làm việc', [
                $this->variant('ACCESSORY-PEDESTAL-WHT', 'Trắng', [400, 500, 600], 'Thép sơn tĩnh điện', 'Trắng', 'Sơn tĩnh điện', 1650000, 3, 'NCC-HP', 'HP-PED-WHT', 1220000),
            ]),
            $this->product('SERVICE-DESIGN', 'Dịch vụ thiết kế bố trí nội thất', 'Design Service', 'SERVICE', 'SALESFLOW-CUSTOM', 'Khảo sát, bố trí mặt bằng 2D và phối cảnh 3D theo phạm vi dự án.', 180000, 0, 'Theo dự án', [
                $this->variant('SERVICE-DESIGN-M2', 'Thiết kế theo m²', [1000, 1000, 0], 'Dịch vụ tư vấn', 'Theo nhận diện khách hàng', 'Bản vẽ 2D/3D', 180000, 7, 'NCC-SF', 'SF-DESIGN-M2', 110000, 'made_to_order'),
            ], 'm²'),
            $this->product('SERVICE-INSTALL', 'Dịch vụ vận chuyển và lắp đặt', 'Install Service', 'SERVICE', 'SALESFLOW-CUSTOM', 'Vận chuyển, lắp đặt, vệ sinh và bàn giao tại công trình.', 8500000, 3, 'Theo dự án', [
                $this->variant('SERVICE-INSTALL-PACK', 'Gói lắp đặt tiêu chuẩn', [0, 0, 0], 'Dịch vụ triển khai', 'Không áp dụng', 'Bàn giao hoàn thiện', 8500000, 5, 'NCC-SF', 'SF-INSTALL-PACK', 6200000, 'made_to_order'),
            ], 'Gói'),
        ];
    }

    private function seedPriceBooks(User $owner): void
    {
        $definitions = [
            ['name' => 'Bảng giá nội thất tiêu chuẩn 2026', 'segment' => 'Doanh nghiệp', 'min_quantity' => 1, 'factor' => 1.00],
            ['name' => 'Bảng giá dự án từ 50 chỗ ngồi', 'segment' => 'Dự án', 'min_quantity' => 10, 'factor' => 0.92],
            ['name' => 'Bảng giá khách hàng chiến lược', 'segment' => 'Key Account', 'min_quantity' => 30, 'factor' => 0.86],
        ];
        $products = Product::query()->where('is_active', true)->orderBy('id')->get();

        foreach ($definitions as $definition) {
            $priceBook = PriceBook::query()->updateOrCreate(
                ['name' => $definition['name']],
                [
                    'customer_segment' => $definition['segment'],
                    'region' => 'Toàn quốc',
                    'currency_code' => 'VND',
                    'effective_from' => '2026-01-01',
                    'effective_until' => '2026-12-31',
                    'is_active' => true,
                    'owner_id' => $owner->id,
                    'department_id' => $owner->department_id,
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );

            foreach ($products as $product) {
                PriceBookEntry::query()->updateOrCreate(
                    [
                        'price_book_id' => $priceBook->id,
                        'product_id' => $product->id,
                        'min_quantity' => $definition['min_quantity'],
                    ],
                    [
                        'unit_price' => round((float) $product->standard_price * $definition['factor'], -3),
                        'vat_percent' => $product->vat_percent,
                        'effective_from' => '2026-01-01',
                        'effective_until' => '2026-12-31',
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     * @return array<string, mixed>
     */
    private function product(
        string $sku,
        string $name,
        string $model,
        string $category,
        string $brand,
        string $description,
        int $basePrice,
        int $warrantyMonths,
        string $usageArea,
        array $variants,
        string $unit = 'Cái',
    ): array {
        return compact('sku', 'name', 'model', 'category', 'brand', 'description', 'unit', 'variants')
            + [
                'base_price' => $basePrice,
                'warranty_months' => $warrantyMonths,
                'usage_area' => $usageArea,
            ];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $dimensions
     * @return array<string, mixed>
     */
    private function variant(
        string $sku,
        string $name,
        array $dimensions,
        string $material,
        string $color,
        string $finish,
        int $price,
        int $leadTimeDays,
        string $supplier,
        string $supplierSku,
        int $purchasePrice,
        string $status = 'active',
    ): array {
        return [
            'sku' => $sku,
            'name' => $name,
            'length_mm' => $dimensions[0],
            'width_mm' => $dimensions[1],
            'height_mm' => $dimensions[2],
            'material' => $material,
            'color' => $color,
            'finish' => $finish,
            'price' => $price,
            'lead_time_days' => $leadTimeDays,
            'status' => $status,
            'supplier' => $supplier,
            'supplier_sku' => $supplierSku,
            'purchase_price' => $purchasePrice,
        ];
    }
}
