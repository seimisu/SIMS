<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Row;

class CheckScholarImport implements OnEachRow, SkipsEmptyRows, ToCollection, WithHeadingRow, WithMultipleSheets
{
    /**
     * @param  Collection  $row
     */
    public $rows;

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
                'contact_no' => ['nullable'],
                'birthdate' => ['required'],
                'birthplace' => ['required'],
                'civil_status' => ['required'],
                'address' => ['nullable'],
                'village' => ['nullable'],
                'barangay' => ['nullable'],
                'municipality' => ['nullable'],
                'province' => ['nullable'],
                'region' => ['nullable'],
                'year_awarded' => ['required'],
                'school' => ['required'],
                'course' => ['required'],
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

    public function collection(Collection $collection)
    {
        $this->rows = $collection;
    }
}
