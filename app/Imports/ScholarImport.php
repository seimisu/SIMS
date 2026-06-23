<?php

namespace App\Imports;

use App\Models\ListCourse;
use App\Models\ListPrograms;
use App\Models\ListReferences;
use App\Models\ListStatuses;
use App\Models\LocationBarangays;
use App\Models\LocationCity;
use App\Models\LocationProvinces;
use App\Models\LocationRegions;
use App\Models\ScholarProfiles;
use App\Models\Scholars;
use App\Models\ScholarSchoolGrades;
use App\Models\SchoolCampusCourses;
use App\Models\SchoolCampuses;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ScholarImport implements OnEachRow, WithHeadingRow, WithStartRow, SkipsEmptyRows, WithMultipleSheets
{
    use Importable;
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function headingRow(): int
    {
        return 1;
    }
    public function startRow(): int
    {
        return 2;
    }

    public function sheets(): array
    {
        return [
            'information' => $this,
        ];
    }

    public function onRow(Row $row)
    {
        $data = $row->toArray();

        DB::transaction(function () use ($data, $row) {

            $scholars = Scholars::create([
                'spas_no'     => trim($data['spas_no']) ?? null,
                'type_id'     =>  ListReferences::where('name', trim($data['scholarship_type']))->value('id') ?? null,
                'program_id'  => ListPrograms::where('name', trim($data['scholarship_subprogram']))->value('id') ?? null,
                'category_id' =>  ListReferences::where('name', trim($data['scholarship_program']))->value('id') ?? null,
                'status_id'   => ListStatuses::where('name', trim($data['status']))->value('id') ?? null,
                'academic_status' => in_array(trim($data['status'] ?? ''), ['Ongoing', 'Graduating', 'Graduated', 'LOA', 'Terminated'], true)
                    ? trim($data['status'])
                    : 'Ongoing',
                'created_by'  => Auth::user()->profile->fullname,
                'award_year' => $data['year_award']
            ]);

            $scholars->profile()->create([
                'fname' => $data['firstname'] ?? null,
                'lname' => $data['lastname'] ?? null,
                'mname' => $data['middlename'] ?? null,
                'suffix' => $data['suffix'] ?? null,
                'contact_no' => $data['contact'] ?? null,
                'birthdate' => Carbon::parse($data['birthdate'])->setTimezone('Asia/Manila')->format('m/d/Y') ?? null,
                'birthplace' => $data['birth_place'] ?? null,
                'email' => $data['email'] ?? null,
                'sex' => $data['sex'] ?? null,
                'religion' => $data['religion'] ?? null,
                'civil_status' => $data['civil_status'] ?? null,

            ]);

            $scholars->parent()->create([
                'fname' => $data['parent_fullname'] ?? null,
                'id_no' => $data['id_no'] ?? null,
                'id_date' => Carbon::parse($data['birthdate'])->setTimezone('Asia/Manila')->format('Y-m-d') ?? null,
                'id_place' => $data['id_place'] ?? null,
                'companion' => $data['companion'] ?? null,
            ]);

            // $sliceName = explode(',', $data['barangay_municipality_province_region']);

            $scholars->address()->create([
                'address' => $data['address'],
                'barangay_code'   => LocationBarangays::where('name', trim($data['barangay']))->value('code') ?? null,
                'municipality_code' => LocationCity::where('name', trim($data['municipality']))->value('code') ?? null,
                'province_code'   => LocationProvinces::where('name', trim($data['province']))->value('code') ?? null,
                'region_code'     => LocationRegions::where('name', trim($data['region']))->value('code') ?? null,
            ]);

            $campus = SchoolCampuses::with('term:id,name')
                ->select('id', 'term_id')
                ->where('generated_name', 'ILIKE', '%' . $data['school'] . '%')
                ->where('is_delete', false)
                ->first();



            $schoolInfo = $scholars->schoolInfo()->create([
                'campus_id' => $campus->id ?? null,
                'campus_course_id' => SchoolCampusCourses::whereHas('course', function ($q) use ($data) {
                    $q->where('name',  'ILIKE', '%' . $data['course'] . '%')->where('is_delete', false);
                })->value('id') ?? null,
            ]);



            // $yearLevel = SchoolCampusCourses::select('years')
            //     ->whereHas('course', function ($q) use ($data) {
            //         $q->where('name', $data['course']);
            //     })
            //     ->first();



            //  $academic_term = ListReferences::select('id', 'name')
            //      ->where('classification', $campus->term?->name)
            //      ->where('type', 'Term')
            //      ->get();



            //  $levels = ListReferences::whereIn('others', range(1, $yearLevel->years))
            //  ->pluck('id', 'others');


            // for ($i = 1; $i <= (int) $yearLevel->years; $i++) {
            //     foreach ($academic_term as $value) {
            //         $termRecords = $scholars->termRecords()->create([
            //             'scholar_school_id' => $schoolInfo->id ?? null,
            //             'term_id'           => $campus->term_id ?? null,
            //             'level_id'          => $levels[(string) $i] ?? null,
            //             'term_type_id'      => $value->id ?? null
            //         ]);
            //     }
            // }





            DB::commit();
        });
    }

    public function rules() {}
}
 // '*.fname' => ['required'],
            // '*.mname' => ['required'],
            // '*.lname' => ['required'],
            // '*.suffix' => ['required'],
            // '*.sex' => ['required', 'in:F,M'],
            // '*.email' => ['required', 'email'],
            // '*.contact' => ['required'],
            // '*.birthdate' => ['required'],
            // '*.birth_place' => ['required'],
            // '*.religion' => ['required'],
            // '*.civil_status' => ['required'],
            // '*.address' => ['required'],
            // '*.barangay' => ['required'],
            // '*.municipality' => ['required'],
            // '*.province' => ['required'],
            // '*.region' => ['required'],
            // '*.parent_fullname' => ['required'],
            // '*.id_no' => ['required'],
            // '*.id_place' => ['required'],
            // '*.id_date' => ['required'],
            // '*.companion' => ['required'],
            // '*.year_award' => ['required'],
            // '*.course' => ['required'],
            // '*.school' => ['required'],
            // '*.campus' => ['required'],
