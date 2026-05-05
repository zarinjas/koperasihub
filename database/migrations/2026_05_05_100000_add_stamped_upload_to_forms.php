<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_forms', function (Blueprint $table) {
            $table->string('submission_method')->default('online_only')->index()->after('success_message');
            $table->text('stamped_upload_instructions')->nullable()->after('submission_method');
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->string('stamped_file_path')->nullable()->after('admin_notes');
            $table->string('stamped_file_original_name')->nullable()->after('stamped_file_path');
            $table->timestamp('stamped_file_uploaded_at')->nullable()->after('stamped_file_original_name');
        });
    }

    public function down(): void
    {
        Schema::table('online_forms', function (Blueprint $table) {
            $table->dropColumn(['submission_method', 'stamped_upload_instructions']);
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropColumn(['stamped_file_path', 'stamped_file_original_name', 'stamped_file_uploaded_at']);
        });
    }
};
