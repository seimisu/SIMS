@php
    $money = fn ($value) => (float) ($value ?? 0);
    $display = fn ($value) => $money($value) > 0 ? $money($value) : '-';
    $region = trim(str_replace('DOST', '', $batch->region ?? ''));
    $title = 'PAYROLL OF REGION ' . ($region ?: '____') . ' - MONITORED DOST UNDERGRADUATE SCHOLARS';
@endphp

<table>
    <tr>
        <td colspan="17" style="text-align: center; font-weight: bold;">{{ $title }}</td>
    </tr>
    <tr><td colspan="17"></td></tr>
    <tr><td colspan="17"></td></tr>

    <tr>
        <th rowspan="2">SPAS ID NO.</th>
        <th rowspan="2">ACCOUNT NO.</th>
        <th rowspan="2">NAME</th>
        <th rowspan="2">SCHOLARSHIP PROGRAM</th>
        <th rowspan="2">UNIVERSITY</th>
        <th rowspan="2" style="color: #ff0000;">SCHOLARSHIP STATUS</th>
        <th rowspan="2">PERIOD COVERED<br>(SCHOOL TERM AND AY)</th>
        <th colspan="5">STIPEND</th>
        <th colspan="2">WITHHELD STIPEND/S</th>
        <th rowspan="2">LEARNING MATERIALS AND/OR CONNECTIVITY ALLOWANCE</th>
        <th rowspan="2">CLOTHING ALLOWANCE<br>(if applicable)</th>
        <th rowspan="2">TOTAL</th>
    </tr>
    <tr>
        @foreach ($monthLabels as $monthLabel)
            <th style="color: #ff0000;">{{ strtoupper($monthLabel) }}</th>
        @endforeach
        <th style="color: #ff0000;">TOTAL AMOUNT</th>
        <th style="color: #ff0000;">REMARKS</th>
    </tr>

    @php
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
                    <td data-format="#,##0.00">{{ $display($row["month_{$month}"] ?? 0) }}</td>
                @endforeach
                <td data-format="#,##0.00">{{ $display($row['total_withheld'] ?? 0) }}</td>
                <td>{{ $row['remarks'] }}</td>
                <td data-format="#,##0.00">{{ $display($row['learning_materials_amount'] ?? 0) }}</td>
                <td data-format="#,##0.00">{{ $display($row['clothing_amount'] ?? 0) }}</td>
                <td data-format="#,##0.00">{{ $display($row['grand_total'] ?? 0) }}</td>
            </tr>
        @endforeach

        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td style="font-weight: bold; text-align: right;">Sub-Total</td>
            @foreach (range(1, 5) as $month)
                <td style="font-weight: bold;" data-format="#,##0.00">{{ $display($subtotal["month_{$month}"]) }}</td>
            @endforeach
            <td style="font-weight: bold;" data-format="#,##0.00">{{ $display($subtotal['withheld']) }}</td>
            <td></td>
            <td style="font-weight: bold;" data-format="#,##0.00">{{ $display($subtotal['learning_materials']) }}</td>
            <td style="font-weight: bold;" data-format="#,##0.00">{{ $display($subtotal['clothing']) }}</td>
            <td style="font-weight: bold;" data-format="#,##0.00">{{ $display($subtotal['total']) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="17" style="text-align: center;">No payroll recipients found.</td>
        </tr>
    @endforelse

    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td style="font-weight: bold; text-align: right;">TOTAL</td>
        @foreach (range(1, 5) as $month)
            <td style="font-weight: bold;" data-format="#,##0.00">{{ $display($grand["month_{$month}"]) }}</td>
        @endforeach
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $display($grand['withheld']) }}</td>
        <td></td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $display($grand['learning_materials']) }}</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $display($grand['clothing']) }}</td>
        <td style="font-weight: bold;" data-format="#,##0.00">{{ $display($grand['total']) }}</td>
    </tr>

    <tr><td colspan="17"></td></tr>
    <tr>
        <td></td>
        <td colspan="14" style="text-align: center;">
            This is to certify that the DOST-SEI undergraduate scholars listed above are of good academic standing and are eligible to receive financial assistance for the {{ $batch->academic_term ?? '____' }} semester/term of AY {{ $batch->school_year ?? '____' }}.
        </td>
    </tr>
    <tr><td colspan="17"></td></tr>
    <tr>
        <td colspan="3" style="font-weight: bold;">PREPARED BY:</td>
    </tr>
    <tr><td colspan="17"></td></tr>
    <tr><td colspan="17"></td></tr>
    <tr><td colspan="17"></td></tr>
    <tr>
        <td colspan="4">Printed Name and Signature of Scholarship Project Staff</td>
    </tr>
    <tr><td colspan="17"></td></tr>
    <tr>
        <td colspan="9"></td>
        <td colspan="3" style="font-weight: bold;">CERTIFIED CORRECT:</td>
    </tr>
    <tr><td colspan="17"></td></tr>
    <tr><td colspan="17"></td></tr>
    <tr><td colspan="17"></td></tr>
    <tr>
        <td colspan="9"></td>
        <td colspan="5">Printed Name and Signature of Scholarship Technical Coordinator</td>
    </tr>
</table>
