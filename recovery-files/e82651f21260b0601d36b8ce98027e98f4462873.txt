<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financing_generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financing_application_id')->constrained('financing_applications')->cascadeOnDelete();
            $table->foreignId('financing_document_template_id')->nullable()->constrained('financing_document_templates')->nullOnDelete();
            $table->string('document_code');
            $table->string('document_name');
            $table->string('document_type')->default('application_form');
            $table->string('source_type')->default('html');
            $table->string('status')->default('pending_generation');
            $table->boolean('requires_upload')->default(true);
            $table->boolean('requires_verification')->default(true);
            $table->string('generated_path')->nullable();
            $table->string('uploaded_path')->nullable();
            $table->string('uploaded_original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->unique(['financing_application_id', 'document_code']);
            $table->index(['cooperative_id', 'financing_application_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financing_generated_documents');
    }
};
