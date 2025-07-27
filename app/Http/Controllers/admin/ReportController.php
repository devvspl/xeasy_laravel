<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CoreFunctions;
use App\Models\CoreVertical;
use App\Models\CoreDepartments;
use App\Models\CoreSubDepartment;
use App\Models\HRMEmployees;
use App\Models\EligibilityPolicy;
use App\Models\ClaimType;
use App\Models\ExpenseClaim;
use App\Exports\ClaimReportExport;
use App\Exports\ClaimTypeWiseClaimReportExport;
use App\Exports\DepartmentWiseClaimReportExport;
use App\Exports\MonthWiseClaimReportExport;
use App\Exports\DailyActivityReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{

    public function claimReport()
    {
        try {

            $functions = CoreFunctions::where('is_active', 1)->get(['id', 'function_name']);
            $verticals = CoreVertical::where('is_active', 1)->get(['id', 'vertical_name']);
            $departments = CoreDepartments::where('is_active', 1)->get(['id', 'department_name']);
            $sub_departments = CoreSubDepartment::where('is_active', 1)->get(['id', 'sub_department_name']);
            $employees = HRMEmployees::select(
                'hrims.hrm_employee.EmployeeID',
                'hrims.hrm_employee.EmpCode',
                'hrims.hrm_employee.Fname',
                'hrims.hrm_employee.Sname',
                'hrims.hrm_employee.Lname',
                'hrims.hrm_employee.EmpStatus'
            )
                ->join('hrims.hrm_employee_general', 'hrims.hrm_employee.EmployeeID', '=', 'hrims.hrm_employee_general.EmployeeID')
                ->get();

            $claimTypes = \DB::table('claimtype as ct')
                ->leftJoin('claimgroup as cg', 'cg.cgId', '=', 'ct.cgId')
                ->where('ct.ClaimStatus', 'A')
                ->whereIn('ct.cgId', [1, 7])
                ->orderBy('cg.cgName')
                ->orderBy('ct.ClaimName')
                ->get([
                    'ct.ClaimId',
                    'ct.ClaimName',
                    'cg.cgName'
                ]);

            $eligibility_policy = EligibilityPolicy::where('CompanyId', session('company_id'))->get(['PolicyId', 'PolicyName']);
            return view('admin.claim_report', compact('functions', 'verticals', 'departments', 'sub_departments', 'employees', 'claimTypes', 'eligibility_policy'));
        } catch (\Exception $e) {

            return back()->withErrors(['error' => 'Could not load the claim report page: ' . $e->getMessage()]);
        }
    }

    public function dailyActivity()
    {
        try {

            return view('admin.daily_activity');
        } catch (\Exception $e) {

            return back()->withErrors(['error' => 'Could not load the daily activity page: ' . $e->getMessage()]);
        }
    }


    private function buildClaimQuery(array $filters, string $table, bool $forCount = false)
    {
        $query = DB::table("{$table}");
        if ($forCount) {
            $query->select("{$table}.ExpId");
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

        
        if (
            !empty($filters['function_ids']) ||
            !empty($filters['vertical_ids']) ||
            !empty($filters['department_ids']) ||
            !empty($filters['sub_department_ids'])
        ) {
            $query->leftJoin('hrims.hrm_employee_general', 'hrims.hrm_employee.EmployeeID', '=', 'hrims.hrm_employee_general.EmployeeID');

            if (!empty($filters['function_ids'])) {
                $query->whereIn('hrims.hrm_employee_general.EmpFunction', $filters['function_ids']);
            }

            if (!empty($filters['vertical_ids'])) {
                $query->whereIn('hrims.hrm_employee_general.EmpVertical', $filters['vertical_ids']);
            }

            if (!empty($filters['department_ids'])) {
                $query->whereIn('hrims.hrm_employee_general.DepartmentId', $filters['department_ids']);
            }

            if (!empty($filters['sub_department_ids'])) {
                $query->whereIn('hrims.hrm_employee_general.SubDepartmentId', $filters['sub_department_ids']);
            }
        }

        
        if (
            !empty($filters['policy_ids']) ||
            !empty($filters['vehicle_types'])
        ) {
            $query->leftJoin('hrims.hrm_employee_eligibility', 'hrims.hrm_employee.EmployeeID', '=', 'hrims.hrm_employee_eligibility.EmployeeID');

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

        return $query
            ->groupBy("{$table}.ExpId")
            ->orderBy("{$table}.ExpId", 'asc');
    }

    public function filterClaims(Request $request)
    {
        try {
            $filters = [
                'function_ids' => $request->input('function_ids', []),
                'vertical_ids' => $request->input('vertical_ids', []),
                'department_ids' => $request->input('department_ids', []),
                'sub_department_ids' => $request->input('sub_department_ids', []),
                'user_ids' => $request->input('user_ids', []),
                'months' => $request->input('months', []),
                'claim_type_ids' => $request->input('claim_type_ids', []),
                'claim_statuses' => $request->input('claim_statuses', []),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'date_type' => $request->input('date_type', 'billDate'),
                'policy_ids' => $request->input('policy_ids', []),
                'vehicle_types' => $request->input('vehicle_types', []),
                'wheeler_type' => $request->input('wheeler_type'),
            ];

            $table = ExpenseClaim::tableName();
            $countQuery = $this->buildClaimQuery($filters, $table, true);
            $totalRecords = $countQuery->get()->count();
            $query = $this->buildClaimQuery($filters, $table);
            return DataTables::of($query)
                ->with('recordsTotal', $totalRecords)
                ->with('recordsFiltered', $totalRecords)
                ->addIndexColumn()
                ->editColumn('ClaimAtStep', function ($row) {
                    $badgeClass = 'bg-secondary-subtle text-secondary';
                    $statusText = 'Unknown';
                    switch ($row->ClaimAtStep) {
                        case 1:
                            $badgeClass = 'bg-dark-subtle text-dark';
                            $statusText = 'Deactivate';
                            break;
                        case 2:
                            $badgeClass = 'bg-warning-subtle text-warning';
                            $statusText = 'Draft / Submitted';
                            break;
                        case 3:
                            $badgeClass = 'bg-info-subtle text-info';
                            $statusText = 'Filled';
                            break;
                        case 4:
                            $badgeClass = 'bg-primary-subtle text-primary';
                            $statusText = 'Verified';
                            break;
                        case 5:
                            $badgeClass = 'bg-success-subtle text-success';
                            $statusText = 'Approved';
                            break;
                        case 6:
                            $badgeClass = 'bg-success-subtle text-success';
                            $statusText = 'Financed';
                            break;
                    }
                    return '<span class="badge ' . $badgeClass . ' badge-border">' . $statusText . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-claim-id=' . $row->ClaimId . ' data-expid=' . $row->ExpId . ' data-bs-target="#claimDetailModal" id="viewClaimDetail"><i class="ri-eye-fill"></i></button>';
                })
                ->rawColumns(['ClaimAtStep', 'action'])
                ->make(true);

        } catch (\Exception $e) {

            return response()->json(['error' => 'Could not load claims: ' . $e->getMessage()], 500);
        }
    }


    public function export(Request $request)
    {

        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');


        $filters = [
            'function_ids' => $request->input('function_ids', []),
            'vertical_ids' => $request->input('vertical_ids', []),
            'department_ids' => $request->input('department_ids', []),
            'user_ids' => $request->input('user_ids', []),
            'months' => $request->input('months', []),
            'claim_type_ids' => $request->input('claim_type_ids', []),
            'claim_statuses' => $request->input('claim_statuses', []),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'date_type' => $request->input('date_type', 'billDate'),
            'policy_ids' => $request->input('policy_ids', []),
            'vehicle_types' => $request->input('vehicle_types', []),
            'wheeler_type' => $request->input('wheeler_type'),
        ];


        $columns = $request->input('columns', []);
        $reportType = $request->input('reportType', 'general');
        $protectSheets = $request->input('protectSheets', false);
        $table = ExpenseClaim::tableName();


        if (empty($columns)) {
            return response()->json(['error' => 'Please select at least one column to export'], 400);
        }

        try {

            $query = $this->buildClaimQuery($filters, $table);

            $export = match ($reportType) {
                'month_wise' => new MonthWiseClaimReportExport($query, $filters, $columns, $protectSheets, $table),
                'department_wise' => new DepartmentWiseClaimReportExport($query, $filters, $columns, $protectSheets, $table),
                'claim_type_wise' => new ClaimTypeWiseClaimReportExport($query, $filters, $columns, $protectSheets, $table),
                default => new ClaimReportExport($query, $filters, $columns, 'Claims', $protectSheets, $table),
            };


            return Excel::download($export, 'expense_claims_' . date('Ymd_His') . '.xlsx');
        } catch (\Exception $e) {


            return response()->json(['error' => 'Could not export claims: ' . $e->getMessage()], 500);
        }
    }


    public function getDailyActivityData(Request $request)
    {
        try {
            $fromDate = $request->input('fromDate');
            $toDate = $request->input('toDate');

            $table = ExpenseClaim::tableName();

            $baseQuery = DB::table("{$table} as e")
                ->where('e.ClaimStatus', '!=', 'Deactivate')
                ->whereNotIn('e.ClaimId', [19, 20, 21]);

            
            $uploadQuery = (clone $baseQuery)
                ->selectRaw('DATE(e.CrDate) AS ActionDate, COUNT(*) AS TotalUpload, 0 AS Punching, 0 AS Verified, 0 AS Approved, 0 AS Financed')
                ->where('e.CrDate', '!=', '0000-00-00')
                ->whereBetween(DB::raw('DATE(e.CrDate)'), [$fromDate, $toDate])
                ->groupBy(DB::raw('DATE(e.CrDate)'));

            
            $punchingQuery = (clone $baseQuery)
                ->selectRaw('DATE(e.FilledDate) AS ActionDate, 0 AS TotalUpload, COUNT(*) AS Punching, 0 AS Verified, 0 AS Approved, 0 AS Financed')
                ->where('e.FilledDate', '!=', '0000-00-00')
                ->whereBetween(DB::raw('DATE(e.FilledDate)'), [$fromDate, $toDate])
                ->groupBy(DB::raw('DATE(e.FilledDate)'));

            
            $verifiedQuery = (clone $baseQuery)
                ->selectRaw('DATE(e.VerifyDate) AS ActionDate, 0 AS TotalUpload, 0 AS Punching, COUNT(*) AS Verified, 0 AS Approved, 0 AS Financed')
                ->where('e.VerifyDate', '!=', '0000-00-00')
                ->whereBetween(DB::raw('DATE(e.VerifyDate)'), [$fromDate, $toDate])
                ->groupBy(DB::raw('DATE(e.VerifyDate)'));

            
            $approvedQuery = (clone $baseQuery)
                ->selectRaw('DATE(e.ApprDate) AS ActionDate, 0 AS TotalUpload, 0 AS Punching, 0 AS Verified, COUNT(*) AS Approved, 0 AS Financed')
                ->where('e.ApprDate', '!=', '0000-00-00')
                ->whereBetween(DB::raw('DATE(e.ApprDate)'), [$fromDate, $toDate])
                ->groupBy(DB::raw('DATE(e.ApprDate)'));

            
            $financedQuery = (clone $baseQuery)
                ->selectRaw('DATE(e.FinancedDate) AS ActionDate, 0 AS TotalUpload, 0 AS Punching, 0 AS Verified, 0 AS Approved, COUNT(*) AS Financed')
                ->where('e.FinancedDate', '!=', '0000-00-00')
                ->whereBetween(DB::raw('DATE(e.FinancedDate)'), [$fromDate, $toDate])
                ->groupBy(DB::raw('DATE(e.FinancedDate)'));

            
            $unionQuery = $uploadQuery
                ->unionAll($punchingQuery)
                ->unionAll($verifiedQuery)
                ->unionAll($approvedQuery)
                ->unionAll($financedQuery);

            
            $finalQuery = DB::table(DB::raw("({$unionQuery->toSql()}) as sub"))
                ->mergeBindings($unionQuery) 
                ->selectRaw('ActionDate, SUM(TotalUpload) AS TotalUpload, SUM(Punching) AS Punching, SUM(Verified) AS Verified, SUM(Approved) AS Approved, SUM(Financed) AS Financed')
                ->groupBy('ActionDate')
                ->orderBy('ActionDate')
                ->get();

            
            $formattedData = $finalQuery->map(function ($row) {
                return [
                    'ActionDate' => $row->ActionDate,
                    'TotalUpload' => $row->TotalUpload,
                    'Punching' => $row->Punching,
                    'Verified' => $row->Verified,
                    'Approved' => $row->Approved,
                    'Financed' => $row->Financed,
                ];
            })->toArray();

            return $this->jsonSuccess($formattedData, 'Daily activity data loaded successfully.');

        } catch (\Exception $e) {

            return $this->jsonError('Could not load daily activity data: ' . $e->getMessage());
        }
    }

    public function exportDailyActivity(Request $request)
    {

        $fromDate = $request->input('fromDate');
        $toDate = $request->input('toDate');
        return Excel::download(
            new DailyActivityReportExport($fromDate, $toDate),
            'daily_activity_report_' . $fromDate . '_to_' . $toDate . '.xlsx'
        );
    }
}