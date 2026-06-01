<?php

use App\Enums\ProgramStatus;
use App\Enums\ProgramType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('program_type');
            $table->string('location')->nullable();
            $table->string('online_url')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->dateTime('registration_deadline')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('status')->default(ProgramStatus::Draft->value);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cooperative_id', 'status']);
            $table->index(['cooperative_id', 'program_type']);
            $table->index(['cooperative_id', 'start_date']);
            $table->index(['cooperative_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
