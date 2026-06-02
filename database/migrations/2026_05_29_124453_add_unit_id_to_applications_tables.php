<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('cooperative_id')->constrained('units')->nullOnDelete();
        });

        Schema::table('financing_applications', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('cooperative_id')->constrained('units')->nullOnDelete();
        });

        Schema::table('ansuran_applications', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('cooperative_id')->constrained('units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });

        Schema::table('financing_applications', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });

        Schema::table('ansuran_applications', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });
    }
};