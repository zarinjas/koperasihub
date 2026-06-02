<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financing_products', function (Blueprint $table): void {
            $table->foreignId('unit_id')->nullable()->after('cooperative_id')->constrained('units')->nullOnDelete();
            $table->json('required_documents_json')->nullable()->after('rate_note');
            $table->text('eligibility_terms')->nullable()->after('required_documents_json');
            $table->text('product_terms')->nullable()->after('eligibility_terms');
            $table->text('application_notes')->nullable()->after('product_terms');
            $table->text('application_instructions')->nullable()->after('application_notes');
            $table->text('required_documents_note')->nullable()->after('application_instructions');
            $table->string('officer_contact_name')->nullable()->after('required_documents_note');
            $table->string('officer_contact_phone')->nullable()->after('officer_contact_name');
            $table->string('officer_contact_email')->nullable()->after('officer_contact_phone');
            $table->string('consent_pdf_path')->nullable()->after('officer_contact_email');
            $table->string('consent_pdf_name')->nullable()->after('consent_pdf_path');
            $table->string('undertaking_pdf_path')->nullable()->after('consent_pdf_name');
            $table->string('undertaking_pdf_name')->nullable()->after('undertaking_pdf_path');
            $table->string('guide_pdf_path')->nullable()->after('undertaking_pdf_name');
            $table->string('guide_pdf_name')->nullable()->after('guide_pdf_path');
            $table->string('official_form_template_pdf_path')->nullable()->after('guide_pdf_name');
            $table->string('official_form_template_pdf_name')->nullable()->after('official_form_template_pdf_path');
        });

        Schema::table('financing_applications', function (Blueprint $table): void {
            $table->string('completed_form_pdf_path')->nullable()->after('employment_notes');
            $table->string('completed_form_original_name')->nullable()->after('completed_form_pdf_path');
            $table->timestamp('completed_form_uploaded_at')->nullable()->after('completed_form_original_name');
        });
    }

    public function down(): void
    {
        Schema::table('financing_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'completed_form_pdf_path',
                'completed_form_original_name',
                'completed_form_uploaded_at',
            ]);
        });

        Schema::table('financing_products', function (Blueprint $table): void {
            $table->dropColumn([
                'unit_id',
                'required_documents_json',
                'eligibility_terms',
                'product_terms',
                'application_notes',
                'application_instructions',
                'required_documents_note',
                'officer_contact_name',
                'officer_contact_phone',
                'officer_contact_email',
                'consent_pdf_path',
                'consent_pdf_name',
                'undertaking_pdf_path',
                'undertaking_pdf_name',
                'guide_pdf_path',
                'guide_pdf_name',
                'official_form_template_pdf_path',
                'official_form_template_pdf_name',
            ]);
            $table->dropForeign(['unit_id']);
        });
    }
};