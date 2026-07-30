<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_public_links', function (Blueprint $table): void {
            $table->jsonb('public_snapshot')->nullable()->after('version_issued');
        });

        DB::statement("CREATE UNIQUE INDEX quote_public_final_response_unique ON quote_customer_responses (quote_public_link_id) WHERE response_type IN ('accept', 'decline')");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS quote_public_final_response_unique');

        Schema::table('quote_public_links', function (Blueprint $table): void {
            $table->dropColumn('public_snapshot');
        });
    }
};
