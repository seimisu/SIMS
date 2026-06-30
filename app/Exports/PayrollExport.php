<?php

namespace App\Exports;

use App\Models\Batches;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollExport implements FromView, ShouldAutoSize, WithEvents, WithStyles
{
    public function __construct(
        private Batches $batch,
        private array $rows,
    ) {
    }

    public function view(): View
    {
        return view('exports.payroll', [
            'batch' => $this->batch,
            'rows' => $this->rows,
            'monthLabels' => collect(range(1, 5))->map(fn ($month) => "Month {$month}"),
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            4 => ['font' => ['bold' => true]],
            5 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = 'Q';
                $recipientRowCount = collect($this->rows)->sum(fn ($programRows) => count($programRows));
                $subtotalRowCount = count($this->rows);
                $emptyRowCount = $recipientRowCount === 0 ? 1 : 0;
                $lastTableRow = 5 + $recipientRowCount + $subtotalRowCount + $emptyRowCount + 1;

                $sheet->getStyle("A1:{$lastColumn}{$lastTableRow}")->getFont()->setName('Arial')->setSize(10);
                $sheet->getStyle("A1:{$lastColumn}5")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle("A4:{$lastColumn}{$lastTableRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle("A4:{$lastColumn}5")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFFFFFFF');

                $sheet->getStyle("F4:F5")->getFont()->getColor()->setARGB('FFFF0000');
                $sheet->getStyle("H5:N5")->getFont()->getColor()->setARGB('FFFF0000');

                $sheet->getStyle("H6:Q{$lastTableRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00;[Red]-#,##0.00;-');

                $sheet->getStyle("A6:G{$lastTableRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("H6:Q{$lastTableRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                foreach (range(6, $lastTableRow) as $row) {
                    if (in_array($sheet->getCell("G{$row}")->getValue(), ['Sub-Total', 'TOTAL'], true)) {
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFont()->setBold(true);
                    }
                }

                foreach (range('A', $lastColumn) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                foreach (['A' => 10, 'B' => 18, 'C' => 30, 'D' => 14, 'E' => 20, 'F' => 16, 'G' => 28, 'N' => 28, 'O' => 22, 'P' => 16, 'Q' => 14] as $column => $width) {
                    $sheet->getColumnDimension($column)->setAutoSize(false)->setWidth($width);
                }

                for ($column = Coordinate::columnIndexFromString('H'); $column <= Coordinate::columnIndexFromString('M'); $column++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(false)->setWidth(13);
                }

                $sheet->getRowDimension(1)->setRowHeight(22);
                $sheet->getRowDimension(4)->setRowHeight(24);
                $sheet->getRowDimension(5)->setRowHeight(44);
            },
        ];
    }
}
