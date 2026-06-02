<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('financing_application_histories');
        Schema::dropIfExists('financing_application_documents');
        Schema::dropIfExists('financing_documents');
        Schema::dropIfExists('financing_guarantors');
        Schema::dropIfExists('financing_applications');
        Schema::dropIfExists('financing_product_fields');
        Schema::dropIfExists('financing_product_sections');
        Schema::dropIfExists('financing_products');
        Schema::dropIfExists('financing_categories');

        Schema::create('financing_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('type');
            $table->string('icon')->nullable();
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
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('min_amount', 12, 2)->nullable();
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->unsignedInteger('min_tenure_months')->nullable();
            $table->unsignedInteger('max_tenure_months')->nullable();
            $table->decimal('annual_rate_percent', 6, 2)->nullable();
            $table->string('rate_image_path')->nullable();
            $table->text('rate_note')->nullable();
            $table->boolean('requires_guarantor')->default(false);
            $table->unsignedInteger('guarantor_count')->default(1);
            $table->boolean('requires_stamped_upload')->default(false);
            $table->text('stamped_upload_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cooperative_id', 'slug']);
            $table->index(['cooperative_id', 'financing_category_id']);
            $table->index(['cooperative_id', 'is_active']);
        });

        Schema::create('financing_product_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financing_product_id')->constrained('financing_products')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('page_break_before')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('financing_product_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financing_product_id')->constrained('financing_products')->cascadeOnDelete();
            $table->foreignId('financing_product_section_id')->nullable()->constrained('financing_product_sections')->nullOnDelete();
            $table->string('label');
            $table->string('field_key');
            $table->string('type');
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('options_json')->nullable();
            $table->json('validation_json')->nullable();
            $table->json('settings_json')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['financing_product_id', 'field_key']);
            $table->index(['financing_product_id', 'financing_product_section_id']);
        });

        Schema::create('financing_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('financing_category_id')->constrained('financing_categories')->cascadeOnDelete();
            $table->foreignId('financing_product_id')->constrained('financing_products')->cascadeOnDelete();
            $table->string('reference_no')->unique();
            $table->decimal('amount_requested', 12, 2);
            $table->unsignedInteger('tenure_months');
            $table->text('purpose')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->decimal('monthly_commitment', 12, 2)->nullable();
            $table->text('employment_notes')->nullable();
            $table->json('custom_answers_json')->nullable();
            $table->string('status')->default('draft');
            $table->text('admin_notes')->nullable();
            $table->string('stamped_form_path')->nullable();
            $table->string('stamped_form_original_name')->nullable();
            $table->timestamp('stamped_form_uploaded_at')->nullable();
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
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cooperative_id', 'status']);
            $table->index(['cooperative_id', 'member_id']);
            $table->index(['cooperative_id', 'financing_product_id']);
        });

        Schema::create('financing_guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financing_application_id')->constrained('financing_applications')->cascadeOnDelete();
            $table->foreignId('guarantor_member_id')->constrained('members')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['financing_application_id', 'guarantor_member_id']);
            $table->index(['cooperative_id', 'status']);
        });

        Schema::create('financing_application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financing_application_id')->constrained('financing_applications')->cascadeOnDelete();
            $table->foreignId('financing_product_field_id')->nullable()->constrained('financing_product_fields')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label');
            $table->string('field_key')->nullable();
            $table->string('file_path');
            $table->string('original_name')->nullable();
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
            $table->timestamp('created_at')->nullable();

            $table->index(['cooperative_id', 'financing_application_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financing_application_histories');
        Schema::dropIfExists('financing_application_documents');
        Schema::dropIfExists('financing_guarantors');
        Schema::dropIfExists('financing_applications');
        Schema::dropIfExists('financing_product_fields');
        Schema::dropIfExists('financing_product_sections');
        Schema::dropIfExists('financing_products');
        Schema::dropIfExists('financing_categories');
    }
};