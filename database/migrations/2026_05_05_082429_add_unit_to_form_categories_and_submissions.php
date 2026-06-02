<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_categories', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('cooperative_id')->constrained('units')->nullOnDelete();
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('online_form_id')->constrained('units')->nullOnDelete();
            $table->string('unit_name_snapshot')->nullable()->after('unit_id');
            $table->foreignId('approved_by')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('reviewed_at');
            $table->foreignId('rejected_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id', 'unit_name_snapshot', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at']);
        });

        Schema::table('form_categories', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });
    }
};