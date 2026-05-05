<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financing_products', function (Blueprint $table): void {
            $table->decimal('annual_rate_percent', 5, 2)->nullable()->after('max_tenure_months');
            $table->string('rate_note', 1000)->nullable()->after('annual_rate_percent');
        });
    }

    public function down(): void
    {
        Schema::table('financing_products', function (Blueprint $table): void {
            $table->dropColumn(['annual_rate_percent', 'rate_note']);
        });
    }
};
