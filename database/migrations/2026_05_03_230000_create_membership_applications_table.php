<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->string('application_no')->index();
            $table->string('full_name');
            $table->string('identity_no')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->text('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country')->default('Malaysia');
            $table->string('occupation')->nullable();
            $table->string('employer_name')->nullable();
            $table->string('employment_no')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedBigInteger('approved_member_id')->nullable()->index();

            $table->text('review_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cooperative_id', 'application_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_applications');
    }
};
