<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Contracts\Queue\ShouldQueue;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Facades\DB;

class ClaimReportExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithEvents, WithTitle, WithChunkReading, ShouldQueue
{
    protected $query;
    protected $filters;
    protected $columns;
    protected $totals;
    protected $sheetName;
    protected $protectSheets;
    protected $table;
    protected $rowNumber;

    public function __construct($query, array $filters, array $columns, string $sheetName = 'Claims', bool $protectSheets = false, $table)
    {
        $this->query = $query;
        $this->filters = $filters;
        $this->columns = $columns;
        $this->totals = [];
        $this->sheetName = $sheetName;
        $this->protectSheets = $protectSheets;
        $this->table = $table;
        $this->rowNumber = 0;
    }

    public function query()
    {
        return $this->query;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    public function headings(): array
    {
        $headingsMap = [
            'exp_id' => 'Exp Id',
            'claim_id' => 'Claim ID',
            'claim_type' => 'Claim Type',
            'claim_status' => 'Claim Status',
            'emp_name' => 'Employee Name',
            'emp_code' => 'Employee Code',
            'function' => 'Function',
            'vertical' => 'Vertical',
            'department' => 'Department',
            'sub_department' => 'Sub Department',
            'policy' => 'Policy',
            'vehicle_type' => 'Vehicle Type',
            'month' => 'Month',
            'upload_date' => 'Upload Date',
            'bill_date' => 'Bill Date',
            'FilledAmt' => 'Filled Amount',
            'FilledDate' => 'Filled Date',
            'odomtr_opening' => 'Odometer Opening',
            'odomtr_closing' => 'Odometer Closing',
            'TotKm' => 'Total KM',
            'WType' => 'Wheeler Type',
            'RatePerKM' => 'Rate Per KM',
            'VerifyAmt' => 'Verified Amount',
            'VerifyTRemark' => 'Verify Remark',
            'VerifyDate' => 'Verify Date',
            'ApprAmt' => 'Approved Amount',
            'ApprTRemark' => 'Approval Remark',
            'ApprDate' => 'Approval Date',
            'FinancedTAmt' => 'Financed Amount',
            'FinancedTRemark' => 'Finance Remark',
            'FinancedDate' => 'Finance Date',
        ];

        return array_map(fn($column) => $headingsMap[$column] ?? $column, $this->columns);
    }

    public function map($row): array
    {
        static $monthMap = [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December'
        ];
        static $wheelerMap = [2 => '2 Wheeler', 4 => '4 Wheeler'];
        static $statusMap = [
        1 => 'Draft',
        2 => 'Submitted',
        3 => 'Filled',
        4 => 'Approved',
        5 => 'Financed',
        6 => 'Payment',
        ];

        $this->rowNumber++;

        $data = [];
        foreach ($this->columns as $index => $column) {
            $value = match ($column) {
                'exp_id' => $row->ExpId ?? '',
                'claim_id' => $row->ClaimId ?? '',
                'claim_type' => $row->claim_type_name ?? '',
                'claim_status' => $statusMap[$row->ClaimAtStep] ?? '',
                'emp_name' => $row->employee_name ?? '',
                'emp_code' => $row->employee_code ?? '',
                'function' => $row->function_name ?? '',
                'vertical' => $row->vertical_name ?? '',
                'department' => $row->department_name ?? '',
                'sub_department' => $row->sub_department_name ?? '',
                'policy' => $row->policy_name ?? '',
                'vehicle_type' => $row->vehicle_type ?? '',
                'month' => $monthMap[$row->ClaimMonth] ?? '',
                'upload_date' => $row->CrDate ?? '',
                'bill_date' => $row->BillDate ?? '',
                'FilledAmt' => $row->FilledTAmt ?? '',
                'FilledDate' => $row->FilledDate ?? '',
                'odomtr_opening' => $row->odomtr_opening ?? '',
                'odomtr_closing' => $row->odomtr_closing ?? '',
                'TotKm' => $row->TotKm ?? 0,
                'WType' => $wheelerMap[$row->WType] ?? '',
                'RatePerKM' => $row->RatePerKM ?? 0,
                'VerifyAmt' => $row->VerifyTAmt ?? 0,
                'VerifyTRemark' => $row->VerifyTRemark ?? '',
                'VerifyDate' => $row->VerifyDate ?? '',
                'ApprAmt' => $row->ApprTAmt ?? 0,
                'ApprTRemark' => $row->ApprTRemark ?? '',
                'ApprDate' => $row->ApprDate ?? '',
                'FinancedTAmt' => $row->FinancedTAmt ?? 0,
                'FinancedTRemark' => $row->FinancedTRemark ?? '',
                'FinancedDate' => $row->FinancedDate ?? '',
                default => '',
            };

            if (in_array($column, ['FilledAmt', 'TotKm', 'RatePerKM', 'VerifyAmt', 'ApprAmt', 'FinancedTAmt']) && is_numeric($value)) {
                $this->totals[$index] = ($this->totals[$index] ?? 0) + $value;
            }

            $data[] = $value;
        }

        return $data;
    }

    public function getSelectedColumns()
    {
        $columnMap = [
            'exp_id' => 'e.ExpId',
            'claim_id' => 'e.ClaimId',
            'claim_type' => 'ct.ClaimName AS claim_type_name',
            'claim_status' => 'e.ClaimAtStep',
            'emp_name' => "CONCAT(emp.Fname, ' ', COALESCE(emp.Sname, ''), ' ', emp.Lname) AS employee_name",
            'emp_code' => 'emp.EmpCode AS employee_code',
            'function' => 'f.function_name',
            'vertical' => 'v.vertical_name',
            'department' => 'd.department_name',
            'sub_department' => 'sd.sub_department_name',
            'policy' => 'p.PolicyName AS policy_name',
            'vehicle_type' => 'ee.VehicleType AS vehicle_type',
            'month' => 'e.ClaimMonth',
            'upload_date' => 'e.CrDate',
            'bill_date' => 'e.BillDate',
            'FilledAmt' => 'e.FilledTAmt',
            'FilledDate' => 'e.FilledDate',
            'odomtr_opening' => 'e.odomtr_opening',
            'odomtr_closing' => 'e.odomtr_closing',
            'TotKm' => 'e.TotKm',
            'WType' => 'e.WType',
            'RatePerKM' => 'e.RatePerKM',
            'VerifyAmt' => 'e.VerifyTAmt',
            'VerifyTRemark' => 'e.VerifyTRemark',
            'VerifyDate' => 'e.VerifyDate',
            'ApprAmt' => 'e.ApprTAmt',
            'ApprTRemark' => 'e.ApprTRemark',
            'ApprDate' => 'e.ApprDate',
            'FinancedTAmt' => 'e.FinancedTAmt',
            'FinancedTRemark' => 'e.FinancedTRemark',
            'FinancedDate' => 'e.FinancedDate',
        ];

        return array_filter(
            array_map(fn($column) => $columnMap[$column] ?? null, $this->columns),
            fn($value) => !is_null($value)
        );
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = Coordinate::stringFromColumnIndex(count($this->columns));
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF001868']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);

        if ($lastRow > 1) {
            $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
                ],
            ]);
        }

        foreach (range(1, count($this->columns)) as $colIndex) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        $sheet->getRowDimension(1)->setRowHeight(25);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = Coordinate::stringFromColumnIndex(count($this->columns));
                $totalRow = $lastRow + 1;

                $sheet->setCellValue("A{$totalRow}", 'Total');

                foreach ($this->totals as $index => $total) {
                    if ($index < count($this->columns)) {
                        $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
                        $sheet->setCellValue("{$columnLetter}{$totalRow}", $total);
                    }
                }

                $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
                    'alignment' => ['horizontal' => 'right'],
                ]);

                if ($this->protectSheets) {
                    $sheet->getProtection()->setSheet(true);
                    $sheet->getProtection()->setPassword('xai2025');
                }
            },
        ];
    }
}
?>