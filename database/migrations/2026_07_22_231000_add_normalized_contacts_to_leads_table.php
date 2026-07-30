<?php

declare(strict_types=1);

use App\Support\LeadContactNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('email_normalized')->nullable()->after('email');
            $table->string('phone_normalized', 30)->nullable()->after('phone');
            $table->string('secondary_phone_normalized', 30)->nullable()->after('secondary_phone');

            $table->index('email_normalized');
            $table->index('phone_normalized');
            $table->index('secondary_phone_normalized');
        });

        DB::table('leads')
            ->select(['id', 'email', 'phone', 'secondary_phone'])
            ->orderBy('id')
            ->chunkById(200, function ($leads): void {
                foreach ($leads as $lead) {
                    DB::table('leads')->where('id', $lead->id)->update([
                        'email_normalized' => LeadContactNormalizer::email($lead->email),
                        'phone_normalized' => LeadContactNormalizer::phone($lead->phone),
                        'secondary_phone_normalized' => LeadContactNormalizer::phone($lead->secondary_phone),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn([
                'email_normalized',
                'phone_normalized',
                'secondary_phone_normalized',
            ]);
        });
    }
};
