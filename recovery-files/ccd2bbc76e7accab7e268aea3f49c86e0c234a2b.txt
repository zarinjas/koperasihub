<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->decimal('salary', 12, 2)->nullable()->after('employment_no');
            $table->string('bank')->nullable()->after('salary');
            $table->string('bank_account')->nullable()->after('bank');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['salary', 'bank', 'bank_account']);
        });
    }
};
