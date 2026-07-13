@php
    $money = fn ($value) => (float) ($value ?? 0);
    $display = fn ($value) => $money($value) > 0 ? number_format($money($value), 2) : '-';
    $customAllowances = collect($customAllowances ?? []);
    $columnCount = 17 + $customAllowances->count();
    $allowanceColumnCount = 2 + $customAllowances->count();
    $allowanceColumnWidth = max(3.2, min(5.5, 22 / max(1, $allowanceColumnCount)));
    $bodyFontSize = match (true) {
        $customAllowances->count() >= 4 => '4.8px',
        $customAllowances->count() >= 2 => '5.3px',
        $customAllowances->isNotEmpty() => '5.8px',
        default => '6.5px',
    };
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
        'custom_allowances' => $customAllowances->mapWithKeys(fn ($allowance) => [$allowance['code'] => 0])->all(),
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
            font-size: {{ $bodyFontSize }};
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
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

        .col-spas { width: 5%; }
        .col-account { width: 6%; }
        .col-name { width: 9.5%; }
        .col-program { width: 6%; }
        .col-university { width: 7.5%; }
        .col-status { width: 5%; }
        .col-period { width: 6.5%; }
        .col-money { width: 3.5%; }
        .col-remarks { width: 5%; }
        .col-allowance { width: {{ $allowanceColumnWidth }}%; }
        .col-total { width: 4%; }
    </style>
</head>
<body>
    <table>
        <tr class="title-row">
            <td colspan="{{ $columnCount }}" class="title">{{ $title }}</td>
        </tr>
        <tr class="blank-row"><td colspan="{{ $columnCount }}" class="spacer"></td></tr>

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
            @foreach ($customAllowances as $allowance)
                <th rowspan="2" class="col-allowance">{{ strtoupper($allowance['name'] ?? $allowance['code']) }}</th>
            @endforeach
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
                    'custom_allowances' => $customAllowances->mapWithKeys(fn ($allowance) => [$allowance['code'] => 0])->all(),
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

                    foreach ($customAllowances as $allowance) {
                        $code = $allowance['code'];
                        $amount = $money($row['custom_allowances'][$code] ?? 0);
                        $subtotal['custom_allowances'][$code] += $amount;
                        $grand['custom_allowances'][$code] += $amount;
                    }
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
                    @foreach ($customAllowances as $allowance)
                        <td class="right">{{ $display($row['custom_allowances'][$allowance['code']] ?? 0) }}</td>
                    @endforeach
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
                @foreach ($customAllowances as $allowance)
                    <td class="right">{{ $display($subtotal['custom_allowances'][$allowance['code']] ?? 0) }}</td>
                @endforeach
                <td class="right">{{ $display($subtotal['total']) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $columnCount }}" class="center">No payroll recipients found.</td>
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
            @foreach ($customAllowances as $allowance)
                <td class="right">{{ $display($grand['custom_allowances'][$allowance['code']] ?? 0) }}</td>
            @endforeach
            <td class="right">{{ $display($grand['total']) }}</td>
        </tr>

        <tr class="blank-row"><td colspan="{{ $columnCount }}" class="spacer"></td></tr>
        <tr class="note-row">
            <td></td>
            <td colspan="{{ $columnCount - 1 }}" class="center">
                This is to certify that the DOST-SEI undergraduate scholars listed above are of good academic standing and are eligible to receive financial assistance for the {{ $batch->academic_term ?? '____' }} semester/term of AY {{ $batch->school_year ?? '____' }}.
            </td>
        </tr>
        <tr class="blank-row"><td colspan="{{ $columnCount }}" class="spacer"></td></tr>
        <tr class="signature-row">
            <td colspan="3" class="bold">PREPARED BY:</td>
            <td colspan="{{ $columnCount - 3 }}"></td>
        </tr>
        <tr class="signature-row"><td colspan="{{ $columnCount }}" class="signature-space"></td></tr>
        <tr class="signature-row">
            <td colspan="4">Printed Name and Signature of Scholarship Project Staff</td>
            <td colspan="{{ $columnCount - 4 }}"></td>
        </tr>
        <tr class="blank-row"><td colspan="{{ $columnCount }}" class="spacer"></td></tr>
        <tr class="signature-row">
            <td colspan="9"></td>
            <td colspan="3" class="bold">CERTIFIED CORRECT:</td>
            <td colspan="{{ $columnCount - 12 }}"></td>
        </tr>
        <tr class="signature-row"><td colspan="{{ $columnCount }}" class="signature-space"></td></tr>
        <tr class="signature-row">
            <td colspan="9"></td>
            <td colspan="5">Printed Name and Signature of Scholarship Technical Coordinator</td>
            <td colspan="{{ $columnCount - 14 }}"></td>
        </tr>
    </table>
</body>
</html>
