<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_resources', function (Blueprint $table) {
            if (! Schema::hasColumn('video_resources', 'thumbnail_path')) {
                $table->string('thumbnail_path')->nullable()->after('thumbnail_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('video_resources', function (Blueprint $table) {
            if (Schema::hasColumn('video_resources', 'thumbnail_path')) {
                $table->dropColumn('thumbnail_path');
            }
        });
    }
};
