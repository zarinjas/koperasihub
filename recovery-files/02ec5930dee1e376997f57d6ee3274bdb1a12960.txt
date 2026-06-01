<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('position')->nullable()->after('gender');
            $table->string('department')->nullable()->after('position');
            $table->string('employer')->nullable()->after('department');
        });

        DB::table('members')->update([
            'position' => DB::raw('occupation'),
            'employer' => DB::raw('employer_name'),
        ]);

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('occupation');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('employer_name');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('occupation')->nullable()->after('gender');
            $table->string('employer_name')->nullable()->after('position');
        });

        DB::table('members')->update([
            'occupation' => DB::raw('position'),
            'employer_name' => DB::raw('employer'),
        ]);

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['position', 'department', 'employer']);
        });
    }
};
