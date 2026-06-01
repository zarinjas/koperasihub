<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->decimal('monthly_fee', 12, 2)->nullable()->after('notes');
            $table->decimal('total_fee', 14, 2)->nullable()->after('monthly_fee');
            $table->decimal('special_savings', 14, 2)->nullable()->after('total_fee');
            $table->decimal('monthly_deduction', 12, 2)->nullable()->after('special_savings');
            $table->decimal('total_debt', 14, 2)->nullable()->after('monthly_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['monthly_fee', 'total_fee', 'special_savings', 'monthly_deduction', 'total_debt']);
        });
    }
};
