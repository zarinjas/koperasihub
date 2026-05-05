<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financing_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('type');
            $table->string('rate_image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cooperative_id', 'slug']);
            $table->index(['cooperative_id', 'type']);
        });

        Schema::create('financing_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financing_category_id')->constrained('financing_categories')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('min_amount', 12, 2)->nullable();
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->unsignedInteger('min_tenure_months')->nullable();
            $table->unsignedInteger('max_tenure_months')->nullable();
            $table->boolean('requires_guarantor')->default(false);
            $table->unsignedInteger('guarantor_count')->default(0);
            $table->json('required_documents_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cooperative_id', 'slug']);
            $table->index(['cooperative_id', 'financing_category_id']);
        });

        Schema::create('financing_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('reference_no');
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financing_category_id')->constrained('financing_categories')->cascadeOnDelete();
            $table->foreignId('financing_product_id')->constrained('financing_products')->cascadeOnDelete();
            $table->decimal('amount_requested', 12, 2);
            $table->unsignedInteger('tenure_months');
            $table->text('purpose');
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->decimal('monthly_commitment', 12, 2)->nullable();
            $table->text('employment_notes')->nullable();
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->unsignedInteger('approved_tenure_months')->nullable();
            $table->text('decision_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cooperative_id', 'reference_no']);
            $table->index(['cooperative_id', 'status']);
            $table->index(['cooperative_id', 'member_id']);
        });

        Schema::create('financing_guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financing_application_id')->constrained('financing_applications')->cascadeOnDelete();
            $table->foreignId('guarantor_member_id')->constrained('members')->cascadeOnDelete();
            $table->string('status');
            $table->text('consent_text')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->string('signature_path')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['financing_application_id', 'guarantor_member_id']);
            $table->index(['cooperative_id', 'status']);
        });

        Schema::create('financing_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financing_application_id')->constrained('financing_applications')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label');
            $table->string('document_key')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->index(['cooperative_id', 'financing_application_id']);
        });

        Schema::create('financing_application_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financing_application_id')->constrained('financing_applications')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['cooperative_id', 'financing_application_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financing_application_histories');
        Schema::dropIfExists('financing_documents');
        Schema::dropIfExists('financing_guarantors');
        Schema::dropIfExists('financing_applications');
        Schema::dropIfExists('financing_products');
        Schema::dropIfExists('financing_categories');
    }
};
