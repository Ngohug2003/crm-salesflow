<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', static function (Blueprint $table): void {
            $table->unsignedInteger('version')->default(1);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->jsonb('issued_snapshot')->nullable();
            $table->index(['status', 'created_at'], 'quotes_status_created_idx');
        });

        Schema::create('quote_approval_rules', static function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('minimum_discount_percent', 5, 2)->default(0);
            $table->decimal('maximum_discount_percent', 5, 2)->nullable();
            $table->decimal('minimum_total_amount', 15, 2)->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('required_role', 80)->nullable();
            $table->boolean('auto_approve')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->timestamps();
            $table->index(['is_active', 'priority'], 'quote_approval_rules_active_idx');
        });

        Schema::create('quote_approval_requests', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('quote_approval_rules')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('quote_version');
            $table->string('status', 30)->default('pending');
            $table->string('required_role', 80)->nullable();
            $table->decimal('discount_percent', 5, 2);
            $table->decimal('total_amount', 15, 2);
            $table->text('request_note')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'required_role', 'submitted_at'], 'quote_approval_inbox_idx');
            $table->unique(['quote_id', 'quote_version'], 'quote_approval_quote_version_unique');
        });

        Schema::create('quote_approval_actions', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('quote_approval_requests')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 30);
            $table->text('reason')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('quote_versions', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->jsonb('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['quote_id', 'version']);
        });

        Schema::create('quote_documents', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('quote_version_id')->constrained('quote_versions')->cascadeOnDelete();
            $table->string('disk', 50)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100)->default('application/pdf');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('sha256', 64)->nullable();
            $table->string('status', 30)->default('processing');
            $table->text('failure_reason')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            $table->unique('quote_version_id');
        });

        Schema::create('quote_branding_settings', static function (Blueprint $table): void {
            $table->id();
            $table->string('company_name')->default('SalesFlow CRM');
            $table->string('tax_code', 30)->nullable();
            $table->string('address')->nullable();
            $table->string('hotline', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('bank_information')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_branding_settings');
        Schema::dropIfExists('quote_documents');
        Schema::dropIfExists('quote_versions');
        Schema::dropIfExists('quote_approval_actions');
        Schema::dropIfExists('quote_approval_requests');
        Schema::dropIfExists('quote_approval_rules');

        Schema::table('quotes', static function (Blueprint $table): void {
            $table->dropIndex('quotes_status_created_idx');
            $table->dropColumn([
                'version', 'discount_percent', 'submitted_at', 'approved_at',
                'issued_at', 'sent_at', 'rejection_reason', 'issued_snapshot',
            ]);
        });
    }
};
