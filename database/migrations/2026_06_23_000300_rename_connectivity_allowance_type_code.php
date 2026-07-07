<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('allowance_types')) {
            return;
        }

        $old = DB::table('allowance_types')->where('code', 'learning_materials_connectivity')->first();
        $new = DB::table('allowance_types')->where('code', 'connectivity')->first();

        if ($old && !$new) {
            DB::table('allowance_types')
                ->where('id', $old->id)
                ->update([
                    'code' => 'connectivity',
                    'updated_at' => now(),
                ]);

            return;
        }

        if ($old && $new && Schema::hasTable('recipient_allowances')) {
            DB::table('recipient_allowances')
                ->where('allowance_type_id', $old->id)
                ->update(['allowance_type_id' => $new->id]);

            DB::table('allowance_types')->where('id', $old->id)->delete();
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('allowance_types')) {
            return;
        }

        DB::table('allowance_types')
            ->where('code', 'connectivity')
            ->update([
                'code' => 'learning_materials_connectivity',
                'updated_at' => now(),
            ]);
    }
};
