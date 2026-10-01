<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ListStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'new', 'icon' => 'IconSparkles', 'type' => 'scholar', 'color_id' => 1],
            ['name' => 'ongoing', 'icon' => 'IconDotsCircleHorizontal', 'type' => 'scholar', 'color_id' => 13],
            ['name' => 'graduating', 'icon' => 'IconProgressCheck', 'type' => 'scholar', 'color_id' => 5, 'is_active' => true, 'is_delete' => false],
            ['name' => 'graduated', 'icon' => 'IconCircleCheck', 'type' => 'scholar', 'color_id' => 8, 'is_active' => true, 'is_delete' => false],
            ['name' => 'terminated', 'icon' => 'IconCircleX', 'type' => 'scholar', 'color_id' => 9, 'is_active' => true, 'is_delete' => false],
            ['name' => 'non-compliance', 'icon' => 'IconExclamationCircle', 'type' => 'scholar', 'color_id' => 16, 'is_active' => true, 'is_delete' => false],
            ['name' => 'no report', 'icon' => 'IconFileAlert', 'type' => 'scholar', 'color_id' => 16],
            ['name' => 'leave of absence', 'icon' => 'IconCalendarX', 'type' => 'scholar', 'color_id' => 24],
            ['name' => 'withdrawn', 'icon' => 'IconFlag', 'type' => 'scholar', 'color_id' => 52],
            ['name' => 'terminated with service obligations', 'icon' => 'IconCircleX', 'type' => 'scholar', 'color_id' => 9],
            ['name' => 'deceased', 'icon' => 'IconGrave2', 'type' => 'scholar', 'color_id' => 55, 'is_active' => true, 'is_delete' => false],
            ['name' => 'good standing', 'icon' => 'IconFileLike', 'type' => 'standing', 'color_id' => 6],
            ['name' => 'continue under probation', 'icon' => 'IconZoomExclamation', 'type' => 'standing', 'color_id' => 2],
            ['name' => 'continue with partial allowance', 'icon' => 'IconMoneybagMinus', 'type' => 'standing', 'color_id' => 36],
            ['name' => 'continued', 'icon' => 'IconCircleCheck', 'type' => 'standing', 'color_id' => 7],
        ];

        foreach ($statuses as $status) {
            DB::table('list_statuses')->updateOrInsert(
                ['name' => $status['name'], 'type' => $status['type']],
                [...$status, 'is_active' => true, 'is_delete' => false, 'updated_at' => now()]
            );
        }
    }
}
