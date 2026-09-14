<?php

namespace Database\Seeders;

use App\Models\ListRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ListRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('list_roles')->insert([
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Full access to all system features and settings.',
                'is_active' => true,
                'is_lock' => true,
                'is_delete' => false,
                'created_by' => 'John Rey Dalit',
                'created_at' => now(),
            ],
            [
                'name' => 'scholarship coordinator',
                'slug' => 'scholarship coordinator',
                'description' => 'Manages scholarship programs and coordinates recipient processes.',
                'is_active' => true,
                'is_lock' => false,
                'is_delete' => false,
                'created_by' => 'John Rey Dalit',
                'created_at' => now(),
            ],
            [
                'name' => 'scholarship staff',
                'slug' => 'scholarship staff',
                'description' => 'Handles scholar programs and administrative support for scholars.',
                'is_active' => true,
                'is_lock' => false,
                'is_delete' => false,
                'created_by' => 'John Rey Dalit',
                'created_at' => now(),
            ],
            [
                'name' => 'regional staff',
                'slug' => 'regional staff',
                'description' => 'Monitor and manage regional operations and activities.',
                'is_active' => true,
                'is_lock' => false,
                'is_delete' => false,
                'created_by' => 'John Rey Dalit',
                'created_at' => now(),
            ],
            [
                'name' => 'Cashier',
                'slug' => 'cashier',
                'description' => 'Access to cashier dashboard tools.',
                'is_active' => true,
                'is_lock' => false,
                'is_delete' => false,
                'created_by' => 'John Rey Dalit',
                'created_at' => now(),
            ],
        ]);
    }
}
