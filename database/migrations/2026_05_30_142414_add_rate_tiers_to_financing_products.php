<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financing_products', function (Blueprint $table) {
            $table->json('rate_tiers_json')->nullable()->after('annual_rate_percent');
        });
    }

    public function down(): void
    {
        Schema::table('financing_products', function (Blueprint $table) {
            $table->dropColumn('rate_tiers_json');
        });
    }
};