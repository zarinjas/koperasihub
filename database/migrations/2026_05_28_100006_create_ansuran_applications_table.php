<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ansuran_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ansuran_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ansuran_product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ansuran_tenure_option_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ansuran_agreement_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('application_no')->unique();
            $table->decimal('full_price', 12, 2);
            $table->decimal('down_payment', 12, 2)->default(0);
            $table->decimal('financed_amount', 12, 2);
            $table->decimal('interest_rate_percent', 5, 2)->default(0);
            $table->integer('tenure_months');
            $table->decimal('monthly_amount', 12, 2);
            $table->decimal('total_payable', 12, 2);
            $table->string('status')->default('pending_guarantor');
            $table->string('delivery_method')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('delivery_status')->nullable();
            $table->string('delivery_tracking_no')->nullable();
            $table->text('agreement_content')->nullable();
            $table->text('signed_agreement_content')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->bigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->bigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->bigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->bigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ansuran_applications');
    }
};