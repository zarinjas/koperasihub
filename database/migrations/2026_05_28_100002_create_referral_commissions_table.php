<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referrer_member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('referred_member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('membership_application_id')->constrained('membership_applications')->cascadeOnDelete();
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->string('status')->default('pending')->index();
            $table->timestamp('eligible_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('payment_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
    }
};