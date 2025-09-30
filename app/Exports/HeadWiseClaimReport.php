<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class HeadWiseClaimReport implements FromQuery, WithChunkReading, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $query;
    protected $filters;
    protected $columns;
    protected $totals;
    protected $sheetName;
    protected $protectSheets;
    protected $table;
    protected $rowNumber;

    public function __construct($query, array $filters, array $columns, string $sheetName, bool $protectSheets, $table)
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
            'emp_name' => 'Employee Name',
            'emp_code' => 'Employee Code',
            'grade' => 'Grade',
            'exp_id' => 'Exp Id',
            'claim_type' => 'Claim Type',
            'expense_head' => 'Expense Head',
            'claim_status' => 'Claim Status',
            'month' => 'Month',
            'upload_date' => 'Upload Date',
            'bill_date' => 'Bill Date',
            'FilledAmt' => 'Filled Amount',
            'VerifyAmt' => 'Verified Amount',
            'ApprAmt' => 'Approved Amount',
            'FinancedTAmt' => 'Financed Amount',
            'claim_id' => 'Claim ID',
            'function' => 'Function',
            'vertical' => 'Vertical',
            'department' => 'Department',
            'sub_department' => 'Sub Department',
            'policy' => 'Policy',
            'vehicle_type' => 'Vehicle Type',
            'FilledDate' => 'Filled Date',
            'odomtr_opening' => 'Odometer Opening',
            'odomtr_closing' => 'Odometer Closing',
            'TotKm' => 'Total KM',
            'WType' => 'Wheeler Type',
            'RatePerKM' => 'Rate Per KM',
            'activity_category' => 'Activity Category',
            'activity_type' => 'Activity Type',
            'traill_no' => 'Trial No.',
            'crop' => 'Crop',
            'variety' => 'Variety',
            'VerifyTRemark' => 'Verify Remark',
            'VerifyDate' => 'Verify Date',
            'ApprTRemark' => 'Approval Remark',
            'ApprDate' => 'Approval Date',
            'FinancedTRemark' => 'Finance Remark',
            'FinancedDate' => 'Finance Date',
        ];

        // Ensure expense_head is always included
        $headings = array_map(fn($column) => $headingsMap[$column] ?? $column, $this->columns);
        if (!in_array('Expense Head', $headings)) {
            $claimTypeIndex = array_search('Claim Type', $headings);
            if ($claimTypeIndex !== false) {
                array_splice($headings, $claimTypeIndex + 1, 0, 'Expense Head');
            } else {
                $headings[] = 'Expense Head';
            }
        }

        return $headings;
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
            12 => 'December',
        ];
        static $wheelerMap = [2 => '2 Wheeler', 4 => '4 Wheeler'];
        $this->rowNumber++;

        // Define ClaimIds that require head-wise details
        $headWiseClaimIds = [16, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42];

        // Fetch detail records for this ExpId if ClaimId is in headWiseClaimIds
        $details = in_array($row->ClaimId, $headWiseClaimIds)
            ? DB::table('y7_expenseclaimsdetails')->where('ExpId', $row->ExpId)->get()
            : collect([]); // Empty collection if not head-wise

        $rows = [];
        $headings = $this->headings();
        $expenseHeadIndex = array_search('Expense Head', $headings);

        // If head-wise details are required and exist
        if (in_array($row->ClaimId, $headWiseClaimIds) && $details->isNotEmpty()) {
            foreach ($details as $detail) {
                $data = [];
                $expenseHeadIncluded = false;
                $currentIndex = 0;
                $dataForTotals = [];

                foreach ($this->columns as $column) {
                    $value = match ($column) {
                        'exp_id' => $row->ExpId ?? '',
                        'claim_id' => $row->ClaimId ?? '',
                        'claim_type' => $row->claim_type_name ?? '',
                        'claim_status' => $row->ClaimStatus ?? '',
                        'emp_name' => $row->employee_name ?? '',
                        'emp_code' => $row->employee_code ?? '',
                        'grade' => $row->grade ?? '',
                        'function' => $row->function_name ?? '',
                        'vertical' => $row->vertical_name ?? '',
                        'department' => $row->department_name ?? '',
                        'sub_department' => $row->sub_department_name ?? '',
                        'policy' => $row->policy_name ?? '',
                        'vehicle_type' => $row->vehicle_type ?? '',
                        'month' => $monthMap[$row->ClaimMonth] ?? '',
                        'upload_date' => $row->CrDate ? (new \DateTime($row->CrDate))->format('d-m-Y') : '',
                        'bill_date' => $row->BillDate ? (new \DateTime($row->BillDate))->format('d-m-Y') : '',
                        'FilledAmt' => $detail->Amount ?? 0,
                        'FilledDate' => $row->FilledDate ? (new \DateTime($row->FilledDate))->format('d-m-Y') : '',
                        'odomtr_opening' => $row->odomtr_opening ?? '',
                        'odomtr_closing' => $row->odomtr_closing ?? '',
                        'TotKm' => $row->TotKm ?? 0,
                        'WType' => $wheelerMap[$row->WType] ?? '',
                        'RatePerKM' => $row->RatePerKM ?? 0,
                        'activity_category' => $row->activity_category ?? '',
                        'activity_type' => $row->activity_type ?? '',
                        'traill_no' => $row->traill_no ?? '',
                        'crop' => $row->crop ?? '',
                        'variety' => $row->variety ?? '',
                        'VerifyAmt' => $detail->VerifierEditAmount ?? 0,
                        'VerifyTRemark' => $row->VerifyTRemark ?? '',
                        'VerifyDate' => $row->VerifyDate ? (new \DateTime($row->VerifyDate))->format('d-m-Y') : '',
                        'ApprAmt' => $detail->ApproverEditAmount ?? 0,
                        'ApprTRemark' => $row->ApprTRemark ?? '',
                        'ApprDate' => $row->ApprDate ? (new \DateTime($row->ApprDate))->format('d-m-Y') : '',
                        'FinancedTAmt' => $detail->FinanceEditAmount ?? 0,
                        'FinancedTRemark' => $row->FinancedTRemark ?? '',
                        'FinancedDate' => $row->FinancedDate ? (new \DateTime($row->FinancedDate))->format('d-m-Y') : '',
                        'expense_head' => $detail->Title ?? '',
                        default => '',
                    };

                    if ($column === 'expense_head') {
                        $expenseHeadIncluded = true;
                    }

                    if (in_array($column, ['FilledAmt', 'TotKm', 'RatePerKM', 'VerifyAmt', 'ApprAmt', 'FinancedTAmt']) && is_numeric($value)) {
                        $dataForTotals[$currentIndex] = $value;
                    }

                    $data[] = $value;
                    $currentIndex++;
                }

                if (!$expenseHeadIncluded) {
                    array_splice($data, $expenseHeadIndex, 0, $detail->Title ?? '');
                    $newDataForTotals = [];
                    foreach ($dataForTotals as $index => $value) {
                        if ($index < $expenseHeadIndex) {
                            $newDataForTotals[$index] = $value;
                        } else {
                            $newDataForTotals[$index + 1] = $value;
                        }
                    }
                    $dataForTotals = $newDataForTotals;
                    $dataForTotals[$expenseHeadIndex] = 0;
                }

                foreach ($dataForTotals as $index => $value) {
                    $this->totals[$index] = ($this->totals[$index] ?? 0) + $value;
                }

                $rows[] = $data;
            }
        } else {
            // For non-head-wise ClaimIds, generate a single row with expense_head from main table
            $data = [];
            $expenseHeadIncluded = false;
            $currentIndex = 0;
            $dataForTotals = [];

            foreach ($this->columns as $column) {
                $value = match ($column) {
                    'exp_id' => $row->ExpId ?? '',
                    'claim_id' => $row->ClaimId ?? '',
                    'claim_type' => $row->claim_type_name ?? '',
                    'claim_status' => $row->ClaimStatus ?? '',
                    'emp_name' => $row->employee_name ?? '',
                    'emp_code' => $row->employee_code ?? '',
                    'grade' => $row->grade ?? '',
                    'function' => $row->function_name ?? '',
                    'vertical' => $row->vertical_name ?? '',
                    'department' => $row->department_name ?? '',
                    'sub_department' => $row->sub_department_name ?? '',
                    'policy' => $row->policy_name ?? '',
                    'vehicle_type' => $row->vehicle_type ?? '',
                    'month' => $monthMap[$row->ClaimMonth] ?? '',
                    'upload_date' => $row->CrDate ? (new \DateTime($row->CrDate))->format('d-m-Y') : '',
                    'bill_date' => $row->BillDate ? (new \DateTime($row->BillDate))->format('d-m-Y') : '',
                    'FilledAmt' => $row->FilledTAmt ?? 0,
                    'FilledDate' => $row->FilledDate ? (new \DateTime($row->FilledDate))->format('d-m-Y') : '',
                    'odomtr_opening' => $row->odomtr_opening ?? '',
                    'odomtr_closing' => $row->odomtr_closing ?? '',
                    'TotKm' => $row->TotKm ?? 0,
                    'WType' => $wheelerMap[$row->WType] ?? '',
                    'RatePerKM' => $row->RatePerKM ?? 0,
                    'activity_category' => $row->activity_category ?? '',
                    'activity_type' => $row->activity_type ?? '',
                    'traill_no' => $row->traill_no ?? '',
                    'crop' => $row->crop ?? '',
                    'variety' => $row->variety ?? '',
                    'VerifyAmt' => $row->VerifyTAmt ?? 0,
                    'VerifyTRemark' => $row->VerifyTRemark ?? '',
                    'VerifyDate' => $row->VerifyDate ? (new \DateTime($row->VerifyDate))->format('d-m-Y') : '',
                    'ApprAmt' => $row->ApprTAmt ?? 0,
                    'ApprTRemark' => $row->ApprTRemark ?? '',
                    'ApprDate' => $row->ApprDate ? (new \DateTime($row->ApprDate))->format('d-m-Y') : '',
                    'FinancedTAmt' => $row->FinancedTAmt ?? 0,
                    'FinancedTRemark' => $row->FinancedTRemark ?? '',
                    'FinancedDate' => $row->FinancedDate ? (new \DateTime($row->FinancedDate))->format('d-m-Y') : '',
                    'expense_head' => $row->expense_head ?? '',
                    default => '',
                };

                if ($column === 'expense_head') {
                    $expenseHeadIncluded = true;
                }

                if (in_array($column, ['FilledAmt', 'TotKm', 'RatePerKM', 'VerifyAmt', 'ApprAmt', 'FinancedTAmt']) && is_numeric($value)) {
                    $dataForTotals[$currentIndex] = $value;
                }

                $data[] = $value;
                $currentIndex++;
            }

            if (!$expenseHeadIncluded) {
                array_splice($data, $expenseHeadIndex, 0, $row->expense_head ?? '');
                $newDataForTotals = [];
                foreach ($dataForTotals as $index => $value) {
                    if ($index < $expenseHeadIndex) {
                        $newDataForTotals[$index] = $value;
                    } else {
                        $newDataForTotals[$index + 1] = $value;
                    }
                }
                $dataForTotals = $newDataForTotals;
                $dataForTotals[$expenseHeadIndex] = 0;
            }

            foreach ($dataForTotals as $index => $value) {
                $this->totals[$index] = ($this->totals[$index] ?? 0) + $value;
            }

            $rows[] = $data;
        }

        return $rows;
    }

    public function getSelectedColumns()
    {
        $columnMap = [
            'exp_id' => 'e.ExpId',
            'claim_id' => 'e.ClaimId',
            'claim_type' => 'ct.ClaimName AS claim_type_name',
            'claim_status' => 'e.ClaimStatus',
            'emp_name' => "CONCAT(emp.Fname, ' ', COALESCE(emp.Sname, ''), ' ', emp.Lname) AS employee_name",
            'emp_code' => 'emp.EmpCode AS employee_code',
            'grade' => 'g.grade_name AS grade',
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
            'activity_category' => 'adv_activity_categories.activity_category',
            'activity_type' => 'adv_activity_types.activity_type',
            'traill_no' => 'e.traill_no',
            'crop' => 'c.crop',
            'variety' => 'vr.variety',
            'VerifyAmt' => 'e.VerifyTAmt',
            'VerifyTRemark' => 'e.VerifyTRemark',
            'VerifyDate' => 'e.VerifyDate',
            'ApprAmt' => 'e.ApprTAmt',
            'ApprTRemark' => 'e.ApprTRemark',
            'ApprDate' => 'e.ApprDate',
            'FinancedTAmt' => 'e.FinancedTAmt',
            'FinancedTRemark' => 'e.FinancedTRemark',
            'FinancedDate' => 'e.FinancedDate',
            'expense_head' => 'ecd.Title AS expense_head',
        ];

        $selectedColumns = array_filter(
            array_map(fn($column) => $columnMap[$column] ?? null, $this->columns),
            fn($value) => !is_null($value)
        );

        // Adjust expense_head selection based on ClaimId
        $headWiseClaimIds = [16, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42];
        if (!in_array('ecd.Title AS expense_head', $selectedColumns)) {
            $selectedColumns['expense_head'] = DB::raw("CASE WHEN e.ClaimId IN (" . implode(',', $headWiseClaimIds) . ") THEN ecd.Title ELSE e.expense_head END AS expense_head");
        }

        return $selectedColumns;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
        $lastRow = $sheet->getHighestRow();

        // Header styling
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '1F4E78']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);

        // Define ClaimIds that require head-wise details and red highlighting
        $headWiseClaimIds = [16, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42];

        // Apply alternating colors based on ExpId and conditional red highlighting
        if ($lastRow > 1) {
            $rowIndex = 2;
            $currentExpId = null;
            $colorIndex = 0;
            $alternateColors = ['FFF5F5F5', 'FFFFFF']; // Light blue and white
            $query = $this->query->get();

            foreach ($query as $row) {
                // Check if ExpId has changed to alternate color
                if ($currentExpId !== $row->ExpId) {
                    $currentExpId = $row->ExpId;
                    $colorIndex = ($colorIndex + 1) % 2; // Toggle between 0 and 1
                }

                // Fetch detail records only for head-wise ClaimIds
                $details = in_array($row->ClaimId, $headWiseClaimIds)
                    ? DB::table('y7_expenseclaimsdetails')->where('ExpId', $row->ExpId)->get()
                    : collect([]);

                // Calculate sum of detail amounts for highlighting (only for head-wise ClaimIds)
                $shouldHighlight = false;
                if (in_array($row->ClaimId, $headWiseClaimIds) && $details->isNotEmpty()) {
                    $detailSum = $details->sum('Amount');
                    $filledAmt = $row->FilledTAmt ?? 0;
                    $shouldHighlight = is_numeric($filledAmt) && is_numeric($detailSum) && $filledAmt != $detailSum;
                }

                // Set color: light red for highlighted rows, otherwise alternate color
                $fillColor = $shouldHighlight ? 'FFCCCC' : $alternateColors[$colorIndex]; // Light red or alternate color

                // Style rows (head-wise or single row)
                if (in_array($row->ClaimId, $headWiseClaimIds) && $details->isNotEmpty()) {
                    foreach ($details as $detail) {
                        $sheet->getStyle("A{$rowIndex}:{$lastColumn}{$rowIndex}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => $fillColor],
                            ],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
                            ],
                        ]);
                        $rowIndex++;
                    }
                } else {
                    // Style single row for non-head-wise ClaimIds
                    $sheet->getStyle("A{$rowIndex}:{$lastColumn}{$rowIndex}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => $fillColor],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
                        ],
                    ]);
                    $rowIndex++;
                }
            }
        }

        // Auto-size columns
        foreach (range(1, count($this->headings())) as $colIndex) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        $sheet->getRowDimension(1)->setRowHeight(18);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
                $totalRow = $lastRow + 1;

                // Set total row values
                $sheet->setCellValue("A{$totalRow}", 'Total');
                $headings = $this->headings();
                foreach ($this->totals as $index => $total) {
                    if ($index < count($headings)) {
                        $columnName = array_key_exists($index, $headings) ? $headings[$index] : '';
                        if (in_array($columnName, ['Filled Amount', 'Total KM', 'Rate Per KM', 'Verified Amount', 'Approved Amount', 'Financed Amount'])) {
                            $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
                            $sheet->setCellValue("{$columnLetter}{$totalRow}", $total);
                        }
                    }
                }

                // Style total row with light yellow background
                $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
                    'alignment' => ['horizontal' => 'right'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFE082'], // Light yellow
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
                    ],
                ]);

                // Protect sheet if enabled
                if ($this->protectSheets) {
                    $sheet->getProtection()->setSheet(true);
                    $sheet->getProtection()->setPassword('xai2025');
                }
            },
        ];
    }
}