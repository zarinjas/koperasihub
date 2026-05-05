<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financing_products', function (Blueprint $table): void {
            $table->string('rate_image_path')->nullable()->after('max_tenure_months');
        });
    }

    public function down(): void
    {
        Schema::table('financing_products', function (Blueprint $table): void {
            $table->dropColumn('rate_image_path');
        });
    }
};
