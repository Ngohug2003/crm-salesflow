<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('full_name');
            $table->string('full_name_en')->nullable();
            $table->string('code_name')->unique();
            $table->unsignedSmallInteger('administrative_unit_id')->nullable();
            $table->string('administrative_unit_short_name')->nullable();
            $table->string('administrative_unit_full_name')->nullable();
            $table->string('administrative_unit_short_name_en')->nullable();
            $table->string('administrative_unit_full_name_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'name']);
        });

        Schema::create('wards', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('full_name');
            $table->string('full_name_en')->nullable();
            $table->string('code_name');
            $table->unsignedSmallInteger('administrative_unit_id')->nullable();
            $table->string('administrative_unit_short_name')->nullable();
            $table->string('administrative_unit_full_name')->nullable();
            $table->string('administrative_unit_short_name_en')->nullable();
            $table->string('administrative_unit_full_name_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // The official source contains distinct ward codes sharing the same
            // code_name inside a merged province, so only the official code is unique.
            $table->index(['province_id', 'code_name']);
            $table->index(['province_id', 'is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wards');
        Schema::dropIfExists('provinces');
    }
};
