@php
    $money = fn ($value) => (float) ($value ?? 0);
    $display = fn ($value) => $money($value) > 0 ? number_format($money($value), 2) : '-';
    $region = trim(str_replace('DOST', '', $batch->region ?? ''));
    $title = 'PAYROLL OF REGION ' . ($region ?: '____') . ' - MONITORED DOST UNDERGRADUATE SCHOLARS';
    $grand = [
        'month_1' => 0,
        'month_2' => 0,
        'month_3' => 0,
        'month_4' => 0,
        'month_5' => 0,
        'withheld' => 0,
        'learning_materials' => 0,
        'clothing' => 0,
        'total' => 0,
    ];
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 18px 14px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 7px;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        th {
            text-align: center;
            font-weight: bold;
        }

        .title-row td,
        .blank-row td,
        .note-row td,
        .signature-row td {
            border: 0;
        }

        .title {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            padding-bottom: 8px;
        }

        .red {
            color: #f00;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .spacer {
            height: 14px;
        }

        .signature-space {
            height: 30px;
        }

        .col-spas { width: 5.5%; }
        .col-account { width: 7%; }
        .col-name { width: 11%; }
        .col-program { width: 7%; }
        .col-university { width: 10%; }
        .col-status { width: 6.5%; }
        .col-period { width: 8.5%; }
        .col-money { width: 5%; }
        .col-remarks { width: 8%; }
        .col-allowance { width: 7.5%; }
        .col-total { width: 5%; }
    </style>
</head>
<body>
    <table>
        <tr class="title-row">
            <td colspan="17" class="title">{{ $title }}</td>
        </tr>
        <tr class="blank-row"><td colspan="17" class="spacer"></td></tr>

        <tr>
            <th rowspan="2" class="col-spas">SPAS ID NO.</th>
            <th rowspan="2" class="col-account">ACCOUNT NO.</th>
            <th rowspan="2" class="col-name">NAME</th>
            <th rowspan="2" class="col-program">SCHOLARSHIP PROGRAM</th>
            <th rowspan="2" class="col-university">UNIVERSITY</th>
            <th rowspan="2" class="col-status red">SCHOLARSHIP STATUS</th>
            <th rowspan="2" class="col-period">PERIOD COVERED<br>(SCHOOL TERM AND AY)</th>
            <th colspan="5">STIPEND</th>
            <th colspan="2">WITHHELD STIPEND/S</th>
            <th rowspan="2" class="col-allowance">LEARNING MATERIALS AND/OR CONNECTIVITY ALLOWANCE</th>
            <th rowspan="2" class="col-allowance">CLOTHING ALLOWANCE<br>(if applicable)</th>
            <th rowspan="2" class="col-total">TOTAL</th>
        </tr>
        <tr>
            @foreach ($monthLabels as $monthLabel)
                <th class="col-money red">{{ strtoupper($monthLabel) }}</th>
            @endforeach
            <th class="col-money red">TOTAL AMOUNT</th>
            <th class="col-remarks red">REMARKS</th>
        </tr>

        @forelse ($rows as $program => $programRows)
            @php
                $subtotal = [
                    'month_1' => 0,
                    'month_2' => 0,
                    'month_3' => 0,
                    'month_4' => 0,
                    'month_5' => 0,
                    'withheld' => 0,
                    'learning_materials' => 0,
                    'clothing' => 0,
                    'total' => 0,
                ];
            @endphp

            @foreach ($programRows as $row)
                @php
                    foreach (range(1, 5) as $month) {
                        $subtotal["month_{$month}"] += $money($row["month_{$month}"] ?? 0);
                        $grand["month_{$month}"] += $money($row["month_{$month}"] ?? 0);
                    }

                    $subtotal['withheld'] += $money($row['total_withheld'] ?? 0);
                    $subtotal['learning_materials'] += $money($row['learning_materials_amount'] ?? 0);
                    $subtotal['clothing'] += $money($row['clothing_amount'] ?? 0);
                    $subtotal['total'] += $money($row['grand_total'] ?? 0);
                    $grand['withheld'] += $money($row['total_withheld'] ?? 0);
                    $grand['learning_materials'] += $money($row['learning_materials_amount'] ?? 0);
                    $grand['clothing'] += $money($row['clothing_amount'] ?? 0);
                    $grand['total'] += $money($row['grand_total'] ?? 0);
                @endphp
                <tr>
                    <td>{{ $row['spas_no'] }}</td>
                    <td>{{ $row['account_no'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['program'] }}</td>
                    <td>{{ $row['university'] }}</td>
                    <td>{{ $row['scholarship_status'] }}</td>
                    <td>{{ $row['period'] }}</td>
                    @foreach (range(1, 5) as $month)
                        <td class="right">{{ $display($row["month_{$month}"] ?? 0) }}</td>
                    @endforeach
                    <td class="right">{{ $display($row['total_withheld'] ?? 0) }}</td>
                    <td>{{ $row['remarks'] }}</td>
                    <td class="right">{{ $display($row['learning_materials_amount'] ?? 0) }}</td>
                    <td class="right">{{ $display($row['clothing_amount'] ?? 0) }}</td>
                    <td class="right">{{ $display($row['grand_total'] ?? 0) }}</td>
                </tr>
            @endforeach

            <tr class="bold">
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="right">Sub-Total</td>
                @foreach (range(1, 5) as $month)
                    <td class="right">{{ $display($subtotal["month_{$month}"]) }}</td>
                @endforeach
                <td class="right">{{ $display($subtotal['withheld']) }}</td>
                <td></td>
                <td class="right">{{ $display($subtotal['learning_materials']) }}</td>
                <td class="right">{{ $display($subtotal['clothing']) }}</td>
                <td class="right">{{ $display($subtotal['total']) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="17" class="center">No payroll recipients found.</td>
            </tr>
        @endforelse

        <tr class="bold">
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td class="right">TOTAL</td>
            @foreach (range(1, 5) as $month)
                <td class="right">{{ $display($grand["month_{$month}"]) }}</td>
            @endforeach
            <td class="right">{{ $display($grand['withheld']) }}</td>
            <td></td>
            <td class="right">{{ $display($grand['learning_materials']) }}</td>
            <td class="right">{{ $display($grand['clothing']) }}</td>
            <td class="right">{{ $display($grand['total']) }}</td>
        </tr>

        <tr class="blank-row"><td colspan="17" class="spacer"></td></tr>
        <tr class="note-row">
            <td></td>
            <td colspan="14" class="center">
                This is to certify that the DOST-SEI undergraduate scholars listed above are of good academic standing and are eligible to receive financial assistance for the {{ $batch->academic_term ?? '____' }} semester/term of AY {{ $batch->school_year ?? '____' }}.
            </td>
            <td colspan="2"></td>
        </tr>
        <tr class="blank-row"><td colspan="17" class="spacer"></td></tr>
        <tr class="signature-row">
            <td colspan="3" class="bold">PREPARED BY:</td>
            <td colspan="14"></td>
        </tr>
        <tr class="signature-row"><td colspan="17" class="signature-space"></td></tr>
        <tr class="signature-row">
            <td colspan="4">Printed Name and Signature of Scholarship Project Staff</td>
            <td colspan="13"></td>
        </tr>
        <tr class="blank-row"><td colspan="17" class="spacer"></td></tr>
        <tr class="signature-row">
            <td colspan="9"></td>
            <td colspan="3" class="bold">CERTIFIED CORRECT:</td>
            <td colspan="5"></td>
        </tr>
        <tr class="signature-row"><td colspan="17" class="signature-space"></td></tr>
        <tr class="signature-row">
            <td colspan="9"></td>
            <td colspan="5">Printed Name and Signature of Scholarship Technical Coordinator</td>
            <td colspan="3"></td>
        </tr>
    </table>
</body>
</html>
