<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ansuran_application_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ansuran_application_id')->constrained()->cascadeOnDelete();
            $table->integer('month_number');
            $table->decimal('amount', 12, 2);
            $table->date('due_date')->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('paid_date')->nullable();
            $table->string('status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->bigInteger('recorded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ansuran_application_payments');
    }
};
