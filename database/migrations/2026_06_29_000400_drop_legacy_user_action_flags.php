<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = collect(['can_create', 'can_edit', 'can_delete'])
                ->filter(fn (string $column) => Schema::hasColumn('users', $column))
                ->all();

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'can_edit')) {
                $table->boolean('can_edit')->default(false)->after('password');
            }

            if (! Schema::hasColumn('users', 'can_create')) {
                $table->boolean('can_create')->default(false)->after('can_edit');
            }

            if (! Schema::hasColumn('users', 'can_delete')) {
                $table->boolean('can_delete')->default(false)->after('can_create');
            }
        });
    }
};
