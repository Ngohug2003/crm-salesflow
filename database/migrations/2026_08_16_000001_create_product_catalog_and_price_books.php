<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 80);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 50)->default('Đơn vị');
            $table->decimal('standard_price', 15, 2)->default(0);
            $table->decimal('vat_percent', 5, 2)->default(10);
            $table->boolean('is_active')->default(true);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('sku', 'products_sku_unique');
            $table->index(['is_active', 'department_id'], 'products_active_department_idx');
        });

        Schema::create('price_books', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('customer_segment', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('currency_code', 3)->default('VND');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'effective_from', 'effective_until'], 'price_books_usable_idx');
        });

        Schema::create('price_book_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('unit_price', 15, 2);
            $table->decimal('vat_percent', 5, 2)->default(10);
            $table->unsignedInteger('min_quantity')->default(1);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['price_book_id', 'product_id', 'min_quantity'], 'price_book_product_quantity_unique');
            $table->index(['price_book_id', 'is_active', 'effective_from', 'effective_until'], 'price_book_entries_usable_idx');
        });

        Schema::table('opportunity_items', function (Blueprint $table): void {
            $table->foreignId('product_id')->nullable()->after('opportunity_id')->constrained()->nullOnDelete();
            $table->foreignId('price_book_entry_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->decimal('vat_percent', 5, 2)->default(0)->after('discount_percent');
        });

        Schema::table('quote_items', function (Blueprint $table): void {
            $table->foreignId('product_id')->nullable()->after('quote_id')->constrained()->nullOnDelete();
            $table->foreignId('price_book_entry_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->decimal('vat_percent', 5, 2)->default(0)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('price_book_entry_id');
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn('vat_percent');
        });
        Schema::table('opportunity_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('price_book_entry_id');
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn('vat_percent');
        });
        Schema::dropIfExists('price_book_entries');
        Schema::dropIfExists('price_books');
        Schema::dropIfExists('products');
    }
};
