<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', static function (Blueprint $table): void {
            $table->string('forecast_category', 30)->nullable()->default('pipeline')->after('stage_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', static function (Blueprint $table): void {
            $table->dropColumn('forecast_category');
        });
    }
};
