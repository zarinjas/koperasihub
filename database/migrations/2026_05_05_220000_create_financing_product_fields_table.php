<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financing_product_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financing_product_id')->constrained('financing_products')->cascadeOnDelete();
            $table->string('label');
            $table->string('field_key', 100);
            $table->string('type', 50)->default('short_text');
            $table->string('placeholder', 500)->nullable();
            $table->string('help_text', 1000)->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('options_json')->nullable();
            $table->json('validation_json')->nullable();
            $table->json('settings_json')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['cooperative_id', 'financing_product_id', 'sort_order']);
            $table->unique(['financing_product_id', 'field_key']);
        });

        Schema::table('financing_applications', function (Blueprint $table): void {
            $table->json('custom_answers_json')->nullable()->after('employment_notes');
        });
    }

    public function down(): void
    {
        Schema::table('financing_applications', function (Blueprint $table): void {
            $table->dropColumn('custom_answers_json');
        });

        Schema::dropIfExists('financing_product_fields');
    }
};
