<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('tax_code', 50)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('name')->nullable();
            $table->unsignedInteger('length_mm')->nullable();
            $table->unsignedInteger('width_mm')->nullable();
            $table->unsignedInteger('height_mm')->nullable();
            $table->string('material')->nullable();
            $table->string('color')->nullable();
            $table->string('finish')->nullable();
            $table->string('unit', 50)->default('Cái');
            $table->decimal('standard_price', 15, 2)->default(0);
            $table->decimal('vat_percent', 5, 2)->default(10);
            $table->unsignedSmallInteger('warranty_months')->default(12);
            $table->unsignedSmallInteger('lead_time_days')->default(0);
            $table->string('commercial_status', 30)->default('active');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->jsonb('specifications')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['product_id', 'commercial_status', 'is_active'], 'product_variants_catalog_idx');
        });

        Schema::create('product_supplier_variant', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_sku', 100)->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->unsignedSmallInteger('lead_time_days')->default(0);
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();
            $table->unique(['product_supplier_id', 'product_variant_id'], 'supplier_variant_unique');
            $table->index(['product_variant_id', 'is_preferred'], 'variant_preferred_supplier_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_supplier_variant');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_suppliers');
    }
};
