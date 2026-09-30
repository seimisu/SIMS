<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addHashColumnIfMissing('batches', 'import_file_hash');
        $this->addHashColumnIfMissing('batches', 'generated_excel_hash');
        $this->addHashColumnIfMissing('scholar_uploaded_files', 'file_hash');
        $this->addHashColumnIfMissing('geolocation_files', 'file_hash');
        $this->addHashColumnIfMissing('documents', 'file_hash');
        $this->addHashColumnIfMissing('video_resources', 'thumbnail_hash');
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
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function addHashColumnIfMissing(string $tableName, string $column): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column) {
            $table->string($column, 64)->nullable()->index();
        });
    }
};
