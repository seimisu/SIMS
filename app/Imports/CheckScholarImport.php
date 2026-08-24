<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Row;
use Illuminate\Support\Collection;

class CheckScholarImport implements OnEachRow, SkipsEmptyRows, ToCollection, WithHeadingRow, WithMultipleSheets
{
    /**
     * @param  Collection  $row
     */
    public $rows;
    public $rowNumbers = [];

    public function headingRow(): int
    {
        return 1;
    }

    public function sheets(): array
    {
        return [
            'Scholars' => $this,
        ];
    }

    public function onRow(Row $row)
    {
        $this->rowNumbers[] = $row->getRowIndex();
    }

    public function collection(Collection $collection)
    {
        $this->rows = $collection;
    }
}
