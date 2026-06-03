<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('spouse_address_line1')->nullable()->after('spouse_address');
            $table->string('spouse_address_line2')->nullable()->after('spouse_address_line1');
            $table->string('spouse_postcode')->nullable()->after('spouse_address_line2');
            $table->string('spouse_city')->nullable()->after('spouse_postcode');
            $table->string('spouse_state')->nullable()->after('spouse_city');

            $table->string('beneficiary_address_line1')->nullable()->after('next_of_kin_address');
            $table->string('beneficiary_address_line2')->nullable()->after('beneficiary_address_line1');
            $table->string('beneficiary_postcode')->nullable()->after('beneficiary_address_line2');
            $table->string('beneficiary_city')->nullable()->after('beneficiary_postcode');
            $table->string('beneficiary_state')->nullable()->after('beneficiary_city');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'spouse_address_line1', 'spouse_address_line2', 'spouse_postcode', 'spouse_city', 'spouse_state',
                'beneficiary_address_line1', 'beneficiary_address_line2', 'beneficiary_postcode', 'beneficiary_city', 'beneficiary_state',
            ]);
        });
    }
};
