<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financing_document_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financing_application_id')->constrained('financing_applications')->cascadeOnDelete();
            $table->foreignId('financing_generated_document_id')->constrained('financing_generated_documents')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['cooperative_id', 'financing_application_id']);
            $table->index(['financing_generated_document_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financing_document_events');
    }
};
