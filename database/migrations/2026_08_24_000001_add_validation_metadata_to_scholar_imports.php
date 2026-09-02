<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholar_uploaded_files', function (Blueprint $table) {
            $table->unsignedInteger('total_rows')->default(0)->after('status');
            $table->unsignedInteger('valid_rows')->default(0)->after('total_rows');
            $table->unsignedInteger('needs_correction_rows')->default(0)->after('valid_rows');
            $table->unsignedInteger('duplicate_rows')->default(0)->after('needs_correction_rows');
            $table->unsignedInteger('missing_required_rows')->default(0)->after('duplicate_rows');
            $table->unsignedInteger('published_rows')->default(0)->after('missing_required_rows');
        });

        Schema::table('scholar_upload_temps', function (Blueprint $table) {
            $table->unsignedInteger('row_number')->nullable()->after('file_id');
            $table->string('row_status')->default('needs_correction')->after('publish_by');
            $table->json('validation_errors')->nullable()->after('row_status');
        });
    }

    public function down(): void
    {
        Schema::table('scholar_uploaded_files', function (Blueprint $table) {
            $table->dropColumn([
                'total_rows',
                'valid_rows',
                'needs_correction_rows',
                'duplicate_rows',
                'missing_required_rows',
                'published_rows',
            ]);
        });

        Schema::table('scholar_upload_temps', function (Blueprint $table) {
            $table->dropColumn([
                'row_number',
                'row_status',
                'validation_errors',
            ]);
        });
    }
};
