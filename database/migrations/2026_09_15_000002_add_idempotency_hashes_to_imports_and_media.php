<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            if (! Schema::hasColumn('batches', 'import_file_hash')) {
                $table->string('import_file_hash', 64)->nullable()->index();
            }

            if (! Schema::hasColumn('batches', 'generated_excel_hash')) {
                $table->string('generated_excel_hash', 64)->nullable()->index();
            }
        });

        Schema::table('scholar_uploaded_files', function (Blueprint $table) {
            if (! Schema::hasColumn('scholar_uploaded_files', 'file_hash')) {
                $table->string('file_hash', 64)->nullable()->index();
            }
        });

        Schema::table('geolocation_files', function (Blueprint $table) {
            if (! Schema::hasColumn('geolocation_files', 'file_hash')) {
                $table->string('file_hash', 64)->nullable()->index();
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'file_hash')) {
                $table->string('file_hash', 64)->nullable()->index();
            }
        });

        Schema::table('video_resources', function (Blueprint $table) {
            if (! Schema::hasColumn('video_resources', 'thumbnail_hash')) {
                $table->string('thumbnail_hash', 64)->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        foreach ([
            'batches' => ['import_file_hash', 'generated_excel_hash'],
            'scholar_uploaded_files' => ['file_hash'],
            'geolocation_files' => ['file_hash'],
            'documents' => ['file_hash'],
            'video_resources' => ['thumbnail_hash'],
        ] as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
