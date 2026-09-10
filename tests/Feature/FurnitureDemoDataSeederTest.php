<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class FurnitureDemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_demo_data_is_coherent_with_furniture_catalog(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('product_categories', 8);
        $this->assertDatabaseCount('product_brands', 4);
        $this->assertDatabaseCount('product_suppliers', 4);
        $this->assertDatabaseCount('products', 22);
        $this->assertDatabaseCount('price_books', 3);
        $this->assertDatabaseCount('price_book_entries', 66);

        $this->assertSame(0, DB::table('products')
            ->whereIn('sku', ['SF-ENTERPRISE', 'APP-MBA-M3-16-512', 'DELL-LAT-5440-I7'])
            ->count());
        $this->assertSame(0, DB::table('product_categories')
            ->whereIn('code', ['LAPTOP', 'MACBOOK', 'DESKTOP', 'MONITOR'])
            ->count());
        $this->assertGreaterThanOrEqual(26, DB::table('product_variants')->count());

        $this->assertGreaterThan(0, DB::table('opportunity_items')->count());
        $this->assertDatabaseCount('opportunities', 120);
        $this->assertSame(0, DB::table('opportunity_items')->whereNull('product_id')->count());
        $this->assertSame(0, DB::table('opportunity_items')->whereNull('price_book_entry_id')->count());
        $this->assertSame(0, DB::table('opportunity_items')
            ->where(fn ($query) => $query
                ->where('sku', 'like', 'CRM-%')
                ->orWhere('sku', 'like', 'INT-%')
                ->orWhere('sku', 'like', 'INFRA-%'))
            ->count());

        $this->assertGreaterThan(0, DB::table('quote_items')->count());
        $this->assertSame(0, DB::table('quote_items')->whereNull('product_id')->count());
        $this->assertSame(0, DB::table('quote_items')->whereNull('price_book_entry_id')->count());
        $this->assertSame(0, DB::table('opportunities')
            ->where(fn ($query) => $query
                ->where('title', 'like', '%CRM%')
                ->orWhere('title', 'like', '%ERP%')
                ->orWhere('title', 'like', '%Platform%'))
            ->count());
        $this->assertTrue(
            Carbon::parse(DB::table('opportunities')->min('created_at'))
                ->lessThanOrEqualTo(now()->subMonthsNoOverflow(23)->endOfMonth()),
        );
    }
}
