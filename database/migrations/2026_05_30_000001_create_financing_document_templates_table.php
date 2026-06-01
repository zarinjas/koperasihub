<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financing_document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financing_product_id')->constrained('financing_products')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type')->default('application_form');
            $table->string('source_type')->default('html');
            $table->boolean('requires_upload')->default(true);
            $table->boolean('requires_verification')->default(true);
            $table->string('template_path')->nullable();
            $table->longText('html_template')->nullable();
            $table->json('settings_json')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['financing_product_id', 'code']);
            $table->index(['cooperative_id', 'financing_product_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financing_document_templates');
    }
};
