<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financing_application_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financing_application_id')->constrained('financing_applications')->cascadeOnDelete();
            $table->foreignId('financing_product_id')->nullable()->constrained('financing_products')->nullOnDelete();
            $table->json('product_snapshot_json');
            $table->json('sections_snapshot_json');
            $table->json('fields_snapshot_json');
            $table->json('document_templates_snapshot_json');
            $table->json('resolved_configuration_json');
            $table->timestamps();

            $table->unique('financing_application_id');
            $table->index(['cooperative_id', 'financing_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financing_application_snapshots');
    }
};