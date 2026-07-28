<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['leads', 'companies', 'contacts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('province_id')
                    ->nullable()
                    ->after('address')
                    ->constrained('provinces')
                    ->nullOnDelete();

                $table->foreignId('ward_id')
                    ->nullable()
                    ->after('province_id')
                    ->constrained('wards')
                    ->nullOnDelete();

                $table->index(['province_id', 'ward_id']);
            });
        }
    }

    public function down(): void
    {
        foreach (['leads', 'companies', 'contacts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['ward_id']);
                $table->dropForeign(['province_id']);
                $table->dropIndex(['province_id', 'ward_id']);
                $table->dropColumn(['province_id', 'ward_id']);
            });
        }
    }
};
