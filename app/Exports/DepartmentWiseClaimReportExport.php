<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Facades\DB;
use App\Exports\ClaimReportExport;

class DepartmentWiseClaimReportExport implements WithMultipleSheets
{
    protected $query;
    protected $filters;
    protected $columns;
    protected $protectSheets;
    protected $table;

    public function __construct($query, array $filters, array $columns, bool $protectSheets = false, $table)
    {
        $this->query = $query;
        $this->filters = $filters;
        $this->columns = $columns;
        $this->protectSheets = $protectSheets;
        $this->table = $table;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Get department IDs and names with a clean subquery
        $departments = DB::table('hrims.core_departments as d')
            ->join('hrims.hrm_employee_general as eg', 'eg.DepartmentId', '=', 'd.id')
            ->join("{$this->table} as e", 'e.CrBy', '=', 'eg.EmployeeID')
            ->select('d.id as DepartmentId', 'd.department_name')
            ->distinct()
            ->get()
            ->mapWithKeys(fn($item) => [$item->DepartmentId => $item->department_name])
            ->toArray();

        if (empty($departments)) {
            $sheets[] = new ClaimReportExport(
                $this->query,
                $this->filters,
                $this->columns,
                'No Data',
                $this->protectSheets,
                $this->table
            );
            return $sheets;
        }

        foreach ($departments as $departmentId => $departmentName) {

            $deptQuery = (clone $this->query)
                ->leftJoin('hrims.hrm_employee_general', 'hrims.hrm_employee_general.EmployeeID', '=', 'hrims.hrm_employee.EmployeeID')
                ->where('hrims.hrm_employee_general.DepartmentId', $departmentId);

            $count = (clone $deptQuery)->count(DB::raw("DISTINCT {$this->table}.ExpId"));

            if ($count > 0) {
                $deptFilters = array_merge($this->filters, ['department_ids' => [$departmentId]]);

                $sheets[] = new ClaimReportExport(
                    $deptQuery,
                    $deptFilters,
                    $this->columns,
                    $departmentName ?: 'Department ' . $departmentId,
                    $this->protectSheets,
                    $this->table
                );
            }
        }


        if (empty($sheets)) {
            $sheets[] = new ClaimReportExport(
                $this->query,
                $this->filters,
                $this->columns,
                'No Data',
                $this->protectSheets,
                $this->table
            );
        }

        return $sheets;
    }
}
