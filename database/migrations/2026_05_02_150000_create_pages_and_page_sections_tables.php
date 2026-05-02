<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('template')->default('default');
            $table->text('summary')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('featured_image_path')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cooperative_id', 'slug']);
            $table->index('cooperative_id');
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('name')->nullable();
            $table->json('data')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('cooperative_id');
            $table->index('page_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('pages');
    }
};
