<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('next_of_kin_name')->nullable()->after('bank_account');
            $table->string('next_of_kin_phone')->nullable()->after('next_of_kin_name');
            $table->text('next_of_kin_address')->nullable()->after('next_of_kin_phone');
            $table->string('spouse_name')->nullable()->after('next_of_kin_address');
            $table->string('spouse_phone')->nullable()->after('spouse_name');
            $table->text('spouse_address')->nullable()->after('spouse_phone');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'next_of_kin_name',
                'next_of_kin_phone',
                'next_of_kin_address',
                'spouse_name',
                'spouse_phone',
                'spouse_address',
            ]);
        });
    }
};
