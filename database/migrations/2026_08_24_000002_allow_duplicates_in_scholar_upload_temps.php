<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE scholar_upload_temps DROP CONSTRAINT IF EXISTS scholar_upload_temps_spas_no_unique');
        DB::statement('ALTER TABLE scholar_upload_temps DROP CONSTRAINT IF EXISTS scholar_upload_temps_email_unique');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE scholar_upload_temps ADD CONSTRAINT scholar_upload_temps_spas_no_unique UNIQUE (spas_no)');
        DB::statement('ALTER TABLE scholar_upload_temps ADD CONSTRAINT scholar_upload_temps_email_unique UNIQUE (email)');
    }
};
