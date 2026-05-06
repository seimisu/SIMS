<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Row;

class CheckScholarImport implements OnEachRow, WithHeadingRow, WithMultipleSheets
{
    /**
     * @param  Collection  $row
     */
    public function headingRow(): int
    {
        return 1;
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
        Validator::make(
            $data,
            [
                'spas_no' => ['required', Rule::unique('scholar_upload_temps', 'spas_no')],
                'status' => ['required'],
                'standing' => ['required'],
                'scholarship_type' => ['required'],
                'scholarship_subprogram' => ['required'],
                'fname' => ['required'],
                'lname' => ['required'],
                'mname' => ['nullable'],
                'suffix' => ['nullable'],
                'sex' => ['required', Rule::in(['M', 'F'])],
                'email' => ['required', 'email', Rule::unique('scholar_upload_temps', 'email')],
                'contact_no' => ['required'],
                'birthdate' => ['required'],
                'birthplace' => ['required'],
                'civil_status' => ['required'],
                'address' => ['required'],
                'barangay' => ['required'],
                'municipality' => ['required'],
                'province' => ['required'],
                'region' => ['required'],
                'year_awarded' => ['required'],
                'course' => ['required'],
                'school' => ['required'],
            ],
            [
                'spas_no.required' => "Row {$row->getRowIndex()}: SPAS No is required.",
                'spas_no.unique' => "Row {$row->getRowIndex()}: SPAS No already exists.",
                'email.email' => "Row {$row->getRowIndex()}: Invalid email format.",
                'email.unique' => "Row {$row->getRowIndex()}: Email already exists.",
                'sex.in' => "Row {$row->getRowIndex()}: Sex must be either M or F.",
                'birthdate.date' => "Row {$row->getRowIndex()}: Invalid birthdate format.",
            ]
        )->validate();
    }
}
