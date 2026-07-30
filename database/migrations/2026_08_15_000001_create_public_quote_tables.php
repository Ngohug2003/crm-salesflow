<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_public_links', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique('quote_public_link_token_unique');
            $table->unsignedInteger('version_issued')->default(1);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('access_code_hash', 255)->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['quote_id', 'revoked_at', 'expires_at'], 'quote_public_link_status_idx');
        });

        Schema::create('quote_customer_responses', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_public_link_id')->constrained()->cascadeOnDelete();
            $table->string('response_type', 20); // accept, decline, comment
            $table->string('signer_name', 150);
            $table->string('signer_email', 150);
            $table->string('signer_title', 150)->nullable();
            $table->text('feedback_notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();

            $table->index(['quote_id', 'response_type'], 'quote_customer_response_type_idx');
        });

        Schema::create('quote_public_events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('quote_public_link_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 40); // viewed, pdf_downloaded, response_submitted
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestamp('created_at');

            $table->index(['quote_public_link_id', 'event_type'], 'quote_public_event_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_public_events');
        Schema::dropIfExists('quote_customer_responses');
        Schema::dropIfExists('quote_public_links');
    }
};
