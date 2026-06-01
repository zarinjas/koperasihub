<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ansuran_agreement_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('content');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ansuran_agreement_templates');
    }
};
