<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_rsvps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('response');
            $table->dateTime('responded_at')->nullable();
            $table->dateTime('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attendance_method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['program_id', 'member_id']);
            $table->index(['cooperative_id', 'response']);
            $table->index(['cooperative_id', 'checked_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_rsvps');
    }
};