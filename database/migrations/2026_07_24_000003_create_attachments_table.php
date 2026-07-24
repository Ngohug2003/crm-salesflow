<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('disk')->default('local');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();

            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
            $table->index(['owner_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
