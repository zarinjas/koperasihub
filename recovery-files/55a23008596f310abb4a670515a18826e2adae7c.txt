<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ansuran_product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ansuran_product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ansuran_product_images');
    }
};
