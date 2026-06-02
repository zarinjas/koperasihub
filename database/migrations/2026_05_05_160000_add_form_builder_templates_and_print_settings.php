<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_sections', function (Blueprint $table) {
            $table->boolean('page_break_before')->default(false)->after('description');
        });

        Schema::create('form_section_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('page_break_before')->default(false);
            $table->json('fields_json');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cooperative_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_section_templates');

        Schema::table('form_sections', function (Blueprint $table) {
            $table->dropColumn('page_break_before');
        });
    }
};