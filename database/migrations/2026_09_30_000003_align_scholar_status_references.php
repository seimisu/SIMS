<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SCHOLAR_STATUSES = [
        ['name' => 'new', 'icon' => 'IconSparkles', 'color_id' => 1],
        ['name' => 'ongoing', 'icon' => 'IconDotsCircleHorizontal', 'color_id' => 13],
        ['name' => 'graduating', 'icon' => 'IconProgressCheck', 'color_id' => 5],
        ['name' => 'graduated', 'icon' => 'IconCircleCheck', 'color_id' => 8],
        ['name' => 'non-compliance', 'icon' => 'IconExclamationCircle', 'color_id' => 16],
        ['name' => 'no report', 'icon' => 'IconFileAlert', 'color_id' => 16],
        ['name' => 'leave of absence', 'icon' => 'IconCalendarX', 'color_id' => 24],
        ['name' => 'withdrawn', 'icon' => 'IconFlag', 'color_id' => 52],
        ['name' => 'terminated', 'icon' => 'IconCircleX', 'color_id' => 9],
        ['name' => 'terminated with service obligations', 'icon' => 'IconCircleX', 'color_id' => 9],
        ['name' => 'deceased', 'icon' => 'IconGrave2', 'color_id' => 55],
    ];

    private const STANDINGS = [
        ['name' => 'good standing', 'icon' => 'IconFileLike', 'color_id' => 6],
        ['name' => 'continue under probation', 'icon' => 'IconZoomExclamation', 'color_id' => 2],
        ['name' => 'continue with partial allowance', 'icon' => 'IconMoneybagMinus', 'color_id' => 36],
        ['name' => 'continued', 'icon' => 'IconCircleCheck', 'color_id' => 7],
    ];

    public function up(): void
    {
        $scholarStatusNames = array_column(self::SCHOLAR_STATUSES, 'name');

        DB::table('list_statuses')
            ->where('type', 'progress')
            ->whereIn(DB::raw('LOWER(name)'), $scholarStatusNames)
            ->update(['type' => 'scholar', 'updated_at' => now()]);

        foreach ([...self::SCHOLAR_STATUSES, ...self::STANDINGS] as $status) {
            $type = in_array($status, self::SCHOLAR_STATUSES, true) ? 'scholar' : 'standing';

            DB::table('list_statuses')->updateOrInsert(
                ['name' => $status['name'], 'type' => $type],
                [
                    'icon' => $status['icon'],
                    'color_id' => $status['color_id'],
                    'is_active' => true,
                    'is_delete' => false,
                    'updated_at' => now(),
                ]
            );
        }

        DB::table('list_statuses')
            ->whereIn('type', ['benefit status', 'qualifier', 'ongoing', 'progress'])
            ->update(['is_active' => false, 'is_delete' => true, 'updated_at' => now()]);

        DB::table('scholars')->whereRaw("UPPER(TRIM(academic_status)) = 'LOA'")
            ->update(['academic_status' => 'LEAVE OF ABSENCE']);
        DB::table('scholars')->whereRaw("UPPER(TRIM(academic_status)) = 'WITHDREW'")
            ->update(['academic_status' => 'WITHDRAWN']);

        foreach (self::SCHOLAR_STATUSES as $status) {
            $id = DB::table('list_statuses')
                ->where('type', 'scholar')
                ->whereRaw('UPPER(name) = ?', [strtoupper($status['name'])])
                ->value('id');

            DB::table('scholars')
                ->whereRaw('UPPER(TRIM(academic_status)) = ?', [strtoupper($status['name'])])
                ->update(['status_id' => $id, 'academic_status' => strtoupper($status['name'])]);
        }
    }

    public function down(): void
    {
        DB::table('list_statuses')
            ->where('type', 'scholar')
            ->update(['type' => 'progress', 'updated_at' => now()]);

        DB::table('list_statuses')
            ->whereIn('type', ['benefit status', 'qualifier', 'ongoing'])
            ->update(['is_active' => true, 'is_delete' => false, 'updated_at' => now()]);
    }
};
