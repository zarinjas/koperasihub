<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'ethnicity')) {
                $table->string('ethnicity')->nullable()->after('gender');
            }

            if (! Schema::hasColumn('members', 'employer_billing_address')) {
                $table->text('employer_billing_address')->nullable()->after('employer');
            }

            if (! Schema::hasColumn('members', 'termination_date')) {
                $table->date('termination_date')->nullable()->after('joined_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'ethnicity',
                'employer_billing_address',
                'termination_date',
            ]);
        });
    }
};
