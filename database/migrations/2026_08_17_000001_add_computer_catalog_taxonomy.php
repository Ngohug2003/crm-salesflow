<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('code', 80)->unique();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('product_brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('code', 80)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('product_category_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('product_brand_id')->nullable()->after('product_category_id')->constrained()->nullOnDelete();
            $table->string('model', 160)->nullable()->after('name');
            $table->jsonb('specifications')->nullable()->after('description');
            $table->unsignedSmallInteger('warranty_months')->default(12)->after('vat_percent');
            $table->string('commercial_status', 30)->default('active')->after('is_active');
            $table->index(['product_category_id', 'product_brand_id', 'commercial_status'], 'products_computer_catalog_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_computer_catalog_idx');
            $table->dropConstrainedForeignId('product_brand_id');
            $table->dropConstrainedForeignId('product_category_id');
            $table->dropColumn(['model', 'specifications', 'warranty_months', 'commercial_status']);
        });
        Schema::dropIfExists('product_brands');
        Schema::dropIfExists('product_categories');
    }
};
