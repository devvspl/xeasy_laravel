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
    protected $filters;
    protected $columns;
    protected $totals;
    protected $sheetName;
    protected $protectSheets;
    protected $table;
    protected $rowNumber;

    public function __construct(array $filters, array $columns, string $sheetName = 'Claims', bool $protectSheets = false, $table)
    {
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
        return $this->buildClaimQuery($this->filters, $this->table, false, $this->columns);
    }

    private function buildClaimQuery(array $filters, string $table, bool $forCount = false, $columns = null)
    {
        $columns = is_array($columns) ? $columns : [];
        $query = DB::table("{$table}");
        if ($forCount) {
            $query->select("{$table}.ExpId");
        } elseif (!empty($columns)) {
            $selects = [];
            if (in_array('exp_id', $columns)) {
                $selects[] = "{$table}.ExpId as ExpId";
            }
            if (in_array('claim_id', $columns)) {
                $selects[] = DB::raw("MAX({$table}.ClaimId) as ClaimId");
            }
            if (in_array('claim_type', $columns)) {
                $selects[] = DB::raw("MAX(claimtype.ClaimName) as claim_type_name");
            }
            if (in_array('claim_status', $columns)) {
                $selects[] = DB::raw("MAX({$table}.ClaimAtStep) as ClaimAtStep");
            }
            if (in_array('emp_name', $columns)) {
                $selects[] = DB::raw("MAX(CONCAT(hrims.hrm_employee.EmpCode, ' - ', hrims.hrm_employee.Fname, ' ', COALESCE(hrims.hrm_employee.Sname, ''), ' ', hrims.hrm_employee.Lname)) as employee_name");
            }
            if (in_array('emp_code', $columns)) {
                $selects[] = DB::raw("MAX(hrims.hrm_employee.EmpCode) as employee_code");
            }
            if (in_array('month', $columns)) {
                $selects[] = DB::raw("MAX({$table}.ClaimMonth) as ClaimMonth");
            }
            if (in_array('upload_date', $columns)) {
                $selects[] = DB::raw("MAX({$table}.CrDate) as CrDate");
            }
            if (in_array('bill_date', $columns)) {
                $selects[] = DB::raw("MAX({$table}.BillDate) as BillDate");
            }
            if (in_array('function', $columns)) {
                $selects[] = DB::raw("MAX(hrims.core_functions.function_name) as function_name");
            }
            if (in_array('vertical', $columns)) {
                $selects[] = DB::raw("MAX(hrims.core_verticals.vertical_name) as vertical_name");
            }
            if (in_array('department', $columns)) {
                $selects[] = DB::raw("MAX(hrims.core_departments.department_name) as department_name");
            }
            if (in_array('sub_department', $columns)) {
                $selects[] = DB::raw("MAX(hrims.core_sub_department_master.sub_department_name) as sub_department_name");
            }
            if (in_array('policy', $columns)) {
                $selects[] = DB::raw("MAX(hrims.hrm_master_eligibility_policy.PolicyName) as policy_name");
            }
            if (in_array('vehicle_type', $columns)) {
                $selects[] = DB::raw("MAX(hrims.hrm_employee_eligibility.VehicleType) as vehicle_type");
            }
            if (in_array('odomtr_opening', $columns)) {
                $selects[] = DB::raw("MAX({$table}.odomtr_opening) as odomtr_opening");
            }
            if (in_array('odomtr_closing', $columns)) {
                $selects[] = DB::raw("MAX({$table}.odomtr_closing) as odomtr_closing");
            }
            if (in_array('TotKm', $columns)) {
                $selects[] = DB::raw("MAX({$table}.TotKm) as TotKm");
            }
            if (in_array('WType', $columns)) {
                $selects[] = DB::raw("MAX({$table}.WType) as WType");
            }
            if (in_array('RatePerKM', $columns)) {
                $selects[] = DB::raw("MAX({$table}.RatePerKM) as RatePerKM");
            }
            if (in_array('FilledAmt', $columns)) {
                $selects[] = DB::raw("MAX({$table}.FilledTAmt) as FilledTAmt");
            }
            if (in_array('FilledDate', $columns)) {
                $selects[] = DB::raw("MAX({$table}.FilledDate) as FilledDate");
            }
            if (in_array('VerifyAmt', $columns)) {
                $selects[] = DB::raw("MAX({$table}.VerifyTAmt) as VerifyTAmt");
            }
            if (in_array('VerifyTRemark', $columns)) {
                $selects[] = DB::raw("MAX({$table}.VerifyTRemark) as VerifyTRemark");
            }
            if (in_array('VerifyDate', $columns)) {
                $selects[] = DB::raw("MAX({$table}.VerifyDate) as VerifyDate");
            }
            if (in_array('ApprAmt', $columns)) {
                $selects[] = DB::raw("MAX({$table}.ApprTAmt) as ApprTAmt");
            }
            if (in_array('ApprTRemark', $columns)) {
                $selects[] = DB::raw("MAX({$table}.ApprTRemark) as ApprTRemark");
            }
            if (in_array('ApprDate', $columns)) {
                $selects[] = DB::raw("MAX({$table}.ApprDate) as ApprDate");
            }
            if (in_array('FinancedTAmt', $columns)) {
                $selects[] = DB::raw("MAX({$table}.FinancedTAmt) as FinancedTAmt");
            }
            if (in_array('FinancedTRemark', $columns)) {
                $selects[] = DB::raw("MAX({$table}.FinancedTRemark) as FinancedTRemark");
            }
            if (in_array('FinancedDate', $columns)) {
                $selects[] = DB::raw("MAX({$table}.FinancedDate) as FinancedDate");
            }
            if (empty($selects)) {
                $selects[] = "{$table}.ExpId as ExpId";
            }
            $query->select($selects);
        } else {
            $query->select([
                "{$table}.ExpId as ExpId",
                DB::raw("MAX(claimtype.ClaimName) as claim_type_name"),
                DB::raw("MAX(CONCAT(hrims.hrm_employee.EmpCode, ' - ', hrims.hrm_employee.Fname, ' ', COALESCE(hrims.hrm_employee.Sname, ''), ' ', hrims.hrm_employee.Lname)) as employee_name"),
                DB::raw("MAX(hrims.hrm_employee.EmpCode) as employee_code"),
                DB::raw("MAX({$table}.ClaimMonth) as ClaimMonth"),
                DB::raw("MAX({$table}.CrDate) as CrDate"),
                DB::raw("MAX({$table}.BillDate) as BillDate"),
                DB::raw("MAX({$table}.FilledTAmt) as FilledTAmt"),
                DB::raw("MAX({$table}.ClaimAtStep) as ClaimAtStep"),
                DB::raw("MAX({$table}.ClaimId) as ClaimId"),
            ]);
        }
        $query->leftJoin('claimtype', "{$table}.ClaimId", '=', 'claimtype.ClaimId');
        $query->leftJoin('hrims.hrm_employee', "{$table}.CrBy", '=', 'hrims.hrm_employee.EmployeeID');
        if (!empty($filters['function_ids']) || !empty($filters['vertical_ids']) || !empty($filters['department_ids']) || !empty($filters['sub_department_ids']) || in_array('function', $columns) || in_array('vertical', $columns) || in_array('department', $columns)) {
            $query->leftJoin('hrims.hrm_employee_general', 'hrims.hrm_employee.EmployeeID', '=', 'hrims.hrm_employee_general.EmployeeID');
            if (in_array('function', $columns)) {
                $query->leftJoin('hrims.core_functions', "hrims.core_functions.id", '=', 'hrims.hrm_employee_general.EmpFunction');
            }
            if (in_array('vertical', $columns)) {
                $query->leftJoin('hrims.core_verticals', "hrims.core_verticals.id", '=', 'hrims.hrm_employee_general.EmpVertical');
            }
            if (in_array('department', $columns)) {
                $query->leftJoin('hrims.core_departments', "hrims.core_departments.id", '=', 'hrims.hrm_employee_general.DepartmentId');
            }
            if (in_array('sub_department', $columns)) {
                $query->leftJoin('hrims.core_sub_department_master', "hrims.core_sub_department_master.id", '=', 'hrims.hrm_employee_general.SubDepartmentId');
            }
            if (!empty($filters['function_ids'])) {
                $query->whereIn('hrims.hrm_employee_general.EmpFunction', $filters['function_ids']);
            }
            if (!empty($filters['vertical_ids'])) {
                $query->whereIn('hrims.hrm_employee_general.EmpVertical', $filters['vertical_ids']);
            }
            if (!empty($filters['department_ids'])) {
                $query->whereIn('hrims.hrm_employee_general.DepartmentId', $filters['department_ids']);
            }
            if ((isset($filters['sub_department_ids']) && !empty($filters['sub_department_ids']))) {
                $query->whereIn('hrims.hrm_employee_general.SubDepartmentId', $filters['sub_department_ids'] ?? []);
            }
        }
        if (!empty($filters['policy_ids']) || !empty($filters['vehicle_types']) || in_array('policy', $columns) || in_array('vehicle_type', $columns)) {
            $query->leftJoin('hrims.hrm_employee_eligibility', 'hrims.hrm_employee.EmployeeID', '=', 'hrims.hrm_employee_eligibility.EmployeeID');
            if (in_array('policy', $columns)) {
                $query->leftJoin('hrims.hrm_master_eligibility_policy', "hrims.hrm_master_eligibility_policy.PolicyId", '=', 'hrims.hrm_employee_eligibility.VehiclePolicy');
            }
            if (!empty($filters['policy_ids'])) {
                $query->whereIn('hrims.hrm_employee_eligibility.VehiclePolicy', $filters['policy_ids']);
            }
            if (!empty($filters['vehicle_types'])) {
                $query->whereIn('hrims.hrm_employee_eligibility.VehicleType', $filters['vehicle_types']);
            }
        }
        if (!empty($filters['user_ids'])) {
            $query->whereIn('hrims.hrm_employee.EmpCode', $filters['user_ids']);
        }
        if (!empty($filters['months'])) {
            $query->whereIn("{$table}.ClaimMonth", $filters['months']);
        }
        if (!empty($filters['claim_type_ids'])) {
            if (in_array(7, $filters['claim_type_ids'])) {
                $query->where("{$table}.ClaimId", 7);
                if (!empty($filters['wheeler_type'])) {
                    $query->where("{$table}.WType", $filters['wheeler_type']);
                }
            } else {
                $query->whereIn("{$table}.ClaimId", $filters['claim_type_ids']);
            }
        }
        if (!empty($filters['claim_statuses'])) {
            $query->whereIn("{$table}.ClaimAtStep", $filters['claim_statuses']);
        }
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $dateColumn = match ($filters['date_type']) {
                'billDate' => 'BillDate',
                'uploadDate' => 'CrDate',
                'filledDate' => 'FilledDate',
                default => 'BillDate',
            };
            $query->whereBetween("{$table}.{$dateColumn}", [$filters['from_date'], $filters['to_date']]);
        }
        return $query->groupBy("{$table}.ExpId")->orderBy("{$table}.ExpId", 'asc');
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