<?php

namespace App\Http\Controllers\admin;

use App\Exports\ClaimReportExport;
use App\Exports\HeadWiseClaimReport;
use App\Exports\ClaimTypeWiseClaimReportExport;
use App\Exports\DailyActivityReportExport;
use App\Exports\DepartmentWiseClaimReportExport;
use App\Exports\MonthWiseClaimReportExport;
use App\Exports\SameDayUploadClaimExport;
use App\Http\Controllers\Controller;
use App\Models\CoreDepartments;
use App\Models\CoreFunctions;
use App\Models\CoreSubDepartment;
use App\Models\CoreVertical;
use App\Models\EligibilityPolicy;
use App\Models\ExpenseClaim;
use App\Models\HRMEmployees;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function claimReport()
    {
        $functions = CoreFunctions::where('is_active', 1)->get(['id', 'function_name']);
        $verticals = CoreVertical::where('is_active', 1)->get(['id', 'vertical_name']);
        $departments = CoreDepartments::where('is_active', 1)->get(['id', 'department_name']);
        $sub_departments = CoreSubDepartment::where('is_active', 1)->get(['id', 'sub_department_name']);
        $employees = HRMEmployees::select('hrims.hrm_employee.EmployeeID', 'hrims.hrm_employee.EmpCode', 'hrims.hrm_employee.Fname', 'hrims.hrm_employee.Sname', 'hrims.hrm_employee.Lname', 'hrims.hrm_employee.EmpStatus')->join('hrims.hrm_employee_general', 'hrims.hrm_employee.EmployeeID', '=', 'hrims.hrm_employee_general.EmployeeID')->get();
        $claimTypes = DB::table('claimtype as ct')->leftJoin('claimgroup as cg', 'cg.cgId', '=', 'ct.cgId')->where('ct.ClaimStatus', 'A')->whereIn('ct.cgId', [1, 7])->orderBy('cg.cgName')->orderBy('ct.ClaimName')->get(['ct.ClaimId', 'ct.ClaimName', 'cg.cgName']);
        $eligibility_policy = EligibilityPolicy::where('CompanyId', session('company_id'))->get(['PolicyId', 'PolicyName']);
        return view('admin.claim_report', compact('functions', 'verticals', 'departments', 'sub_departments', 'employees', 'claimTypes', 'eligibility_policy'));
    }
    public function topRatingEmployee(Request $request)
    {
        $fromDate = $request->query('fromDate', date('Y-m-d'));
        $toDate = $request->query('toDate', date('Y-m-d'));
        $year_id = session('year_id');
        $table = ExpenseClaim::tableName($year_id);
        $data = DB::table("$table as ec")->leftJoin('hrims.hrm_employee_general as eg', 'ec.CrBy', '=', 'eg.EmployeeID')->leftJoin('hrims.hrm_employee as emp', 'ec.CrBy', '=', 'emp.EmployeeID')->leftJoin('hrims.core_verticals as cv', 'cv.id', '=', 'eg.EmpVertical')->leftJoin('hrims.core_departments as cd', 'cd.id', '=', 'eg.DepartmentId')->leftJoin('hrims.core_grades as cg', 'cg.id', '=', 'eg.GradeId')->select(DB::raw("CONCAT_WS(' ', emp.Fname, emp.Sname, emp.Lname) AS EmployeeName"), 'emp.EmpCode', 'cg.grade_name', 'cv.vertical_name', 'cd.department_name', DB::raw('COUNT(ec.ExpId) AS TotalClaimsUploaded'))->whereBetween(DB::raw('DATE(ec.CrDate)'), [$fromDate, $toDate])->whereIn('ec.CrBy', function ($query) use ($table) {
            $query->select('ec_inner.CrBy')->from("$table as ec_inner")->groupBy('ec_inner.CrBy')->havingRaw('SUM(CASE WHEN DATE(ec_inner.BillDate) = DATE(ec_inner.CrDate) THEN 1 ELSE 0 END) = COUNT(*)');
        })->groupBy('emp.EmployeeID', 'emp.Fname', 'emp.Sname', 'emp.Lname', 'emp.EmpCode', 'cg.grade_name', 'cv.vertical_name', 'cd.department_name')->orderByDesc('TotalClaimsUploaded')->orderBy('emp.EmpCode')->get();
        return view('admin.top_rating_employee', compact('data', 'fromDate', 'toDate'));
    }
    public function dailyActivity()
    {
        return view(view: 'admin.daily_activity');
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
                $selects[] = DB::raw('MAX(claimtype.ClaimName) as claim_type_name');
            }
            if (in_array('claim_status', $columns)) {
                $selects[] = DB::raw("MAX({$table}.ClaimStatus) as ClaimStatus");
            }
            if (in_array('emp_name', $columns)) {
                $selects[] = DB::raw("MAX(CONCAT(hrims.hrm_employee.EmpCode, ' - ', hrims.hrm_employee.Fname, ' ', COALESCE(hrims.hrm_employee.Sname, ''), ' ', hrims.hrm_employee.Lname)) as employee_name");
            }
            if (in_array('emp_code', $columns)) {
                $selects[] = DB::raw('MAX(hrims.hrm_employee.EmpCode) as employee_code');
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
                $selects[] = DB::raw('MAX(hrims.core_functions.function_name) as function_name');
            }
            if (in_array('vertical', $columns)) {
                $selects[] = DB::raw('MAX(hrims.core_verticals.vertical_name) as vertical_name');
            }
            if (in_array('department', $columns)) {
                $selects[] = DB::raw('MAX(hrims.core_departments.department_name) as department_name');
            }
            if (in_array('sub_department', $columns)) {
                $selects[] = DB::raw('MAX(hrims.core_sub_department_master.sub_department_name) as sub_department_name');
            }
            if (in_array('grade', $columns)) {
                $selects[] = DB::raw('MAX(hrims.core_grades.grade_name) as grade');
            }
            if (in_array('policy', $columns)) {
                $selects[] = DB::raw('MAX(hrims.hrm_master_eligibility_policy.PolicyName) as policy_name');
            }
            if (in_array('vehicle_type', $columns)) {
                $selects[] = DB::raw('MAX(hrims.hrm_employee_eligibility.VehicleType) as vehicle_type');
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
            $query->select(["{$table}.ExpId as ExpId", DB::raw('MAX(claimtype.ClaimName) as claim_type_name'), DB::raw("MAX(CONCAT(hrims.hrm_employee.EmpCode, ' - ', hrims.hrm_employee.Fname, ' ', COALESCE(hrims.hrm_employee.Sname, ''), ' ', hrims.hrm_employee.Lname)) as employee_name"), DB::raw('MAX(hrims.hrm_employee.EmpCode) as employee_code'), DB::raw("MAX({$table}.ClaimMonth) as ClaimMonth"), DB::raw("MAX({$table}.CrDate) as CrDate"), DB::raw("MAX({$table}.BillDate) as BillDate"), DB::raw("MAX({$table}.FilledTAmt) as FilledTAmt"), DB::raw("MAX({$table}.ClaimAtStep) as ClaimAtStep"), DB::raw("MAX({$table}.ClaimStatus) as ClaimStatus"), DB::raw("MAX({$table}.ClaimId) as ClaimId")]);
        }
        $query->leftJoin('claimtype', "{$table}.ClaimId", '=', 'claimtype.ClaimId');
        $query->leftJoin('hrims.hrm_employee', "{$table}.CrBy", '=', 'hrims.hrm_employee.EmployeeID');
        if (!empty($filters['function_ids']) || !empty($filters['vertical_ids']) || !empty($filters['department_ids']) || !empty($filters['sub_department_ids']) || in_array('function', $columns) || in_array('grade', $columns) || in_array('vertical', $columns) || in_array('department', $columns)) {
            $query->leftJoin('hrims.hrm_employee_general', 'hrims.hrm_employee.EmployeeID', '=', 'hrims.hrm_employee_general.EmployeeID');
            if (in_array('function', $columns)) {
                $query->leftJoin('hrims.core_functions', 'hrims.core_functions.id', '=', 'hrims.hrm_employee_general.EmpFunction');
            }
            if (in_array('vertical', $columns)) {
                $query->leftJoin('hrims.core_verticals', 'hrims.core_verticals.id', '=', 'hrims.hrm_employee_general.EmpVertical');
            }
            if (in_array('department', $columns)) {
                $query->leftJoin('hrims.core_departments', 'hrims.core_departments.id', '=', 'hrims.hrm_employee_general.DepartmentId');
            }
            if (in_array('sub_department', $columns)) {
                $query->leftJoin('hrims.core_sub_department_master', 'hrims.core_sub_department_master.id', '=', 'hrims.hrm_employee_general.SubDepartmentId');
            }
            if (in_array('grade', $columns)) {
                $query->leftJoin('hrims.core_grades', 'hrims.core_grades.id', '=', 'hrims.hrm_employee_general.GradeId');
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
            if (!empty($filters['sub_department_ids'])) {
                $query->whereIn('hrims.hrm_employee_general.SubDepartmentId', $filters['sub_department_ids'] ?? []);
            }
        }
        if (!empty($filters['policy_ids']) || !empty($filters['vehicle_types']) || in_array('policy', $columns) || in_array('vehicle_type', $columns)) {
            $query->leftJoin('hrims.hrm_employee_eligibility', 'hrims.hrm_employee.EmployeeID', '=', 'hrims.hrm_employee_eligibility.EmployeeID');
            if (in_array('policy', $columns)) {
                $query->leftJoin('hrims.hrm_master_eligibility_policy', 'hrims.hrm_master_eligibility_policy.PolicyId', '=', 'hrims.hrm_employee_eligibility.VehiclePolicy');
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
            $status = $filters['claim_statuses'];
            $isType7 = (!empty($filters['claim_type_ids']) && in_array(7, $filters['claim_type_ids']));
            $query->where(function ($q) use ($status, $isType7, $table) {
                if ($status === 'Submitted') {
                    $q->whereIn("{$table}.ClaimStatus", ['Submitted', 'Filled', 'Verified', 'Approved', 'Financed']);
                } elseif ($status === 'Filled') {
                    if ($isType7) {
                        $q->whereIn("{$table}.ClaimStatus", ['Filled', 'Verified', 'Approved', 'Financed'])->where("{$table}.v_verify", 'Y')->where("{$table}.c_verify", 'Y');
                    } else {
                        $q->whereIn("{$table}.ClaimStatus", ['Filled', 'Verified', 'Approved', 'Financed']);
                    }
                } elseif ($status === 'Verified') {
                    if ($isType7) {
                        $q->whereIn("{$table}.ClaimStatus", ['Verified', 'Approved', 'Financed'])->where("{$table}.v_verify", 'Y')->where("{$table}.c_verify", 'Y');
                    } else {
                        $q->whereIn("{$table}.ClaimStatus", ['Verified', 'Approved', 'Financed']);
                    }
                } elseif ($status === 'Approved') {
                    if ($isType7) {
                        $q->whereIn("{$table}.ClaimStatus", ['Approved', 'Financed'])->where("{$table}.v_verify", 'Y')->where("{$table}.c_verify", 'Y');
                    } else {
                        $q->whereIn("{$table}.ClaimStatus", ['Approved', 'Financed']);
                    }
                } elseif ($status === 'Financed') {
                    $q->where("{$table}.ClaimStatus", 'Financed');
                } else {
                    if ($isType7) {
                        $q->where("{$table}.v_verify", 'Y')->where("{$table}.c_verify", 'Y');
                    } else {
                        $q->whereRaw('1=1');
                    }
                }
            });
        } else {
            if (!empty($filters['claim_type_ids']) && in_array(7, $filters['claim_type_ids'])) {
                $query->where("{$table}.v_verify", 'Y')->where("{$table}.c_verify", 'Y');
            }
        }
        if (!empty($filters['claim_filter_type']) && $filters['claim_filter_type'] == 'deactivate_after_filling') {
            $query->where("{$table}.ClaimAtStep", 1)->where("{$table}.ClaimStatus", 'Deactivate')->where("{$table}.FilledDate", '!=', '0000-00-00')->where("{$table}.FilledBy", '>', 0)->where("{$table}.FilledTAmt", '>', 0)->where("{$table}.ClaimId", '!=', 0);
        }
        if (!empty($filters['claim_filter_type']) && $filters['claim_filter_type'] == 'expense_sunday_holiday') {
            $query->leftJoin('hrims.hrm_employee_attendance as ho', function ($join) use ($table) {
                $join->on('ho.AttDate', '=', "{$table}.BillDate")->where('ho.AttValue', '=', 'HO')->where('ho.Year', '=', date('Y'));
            });
            $query->where(function ($q) use ($table) {
                $q->whereRaw("DAYOFWEEK({$table}.BillDate) = 1")->orWhereNotNull('ho.AttDate');
            });
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
        return $query->whereNotIn("{$table}.ClaimStatus", ['Draft', 'Deactivate'])->groupBy("{$table}.ExpId")->orderBy("{$table}.ExpId", 'DESC');
    }
    public function filterClaims(Request $request)
    {
        try {
            $filters = ['function_ids' => $request->input('function_ids', []), 'vertical_ids' => $request->input('vertical_ids', []), 'department_ids' => $request->input('department_ids', []), 'sub_department_ids' => $request->input('sub_department_ids', []), 'user_ids' => $request->input('user_ids', []), 'months' => $request->input('months', []), 'claim_type_ids' => $request->input('claim_type_ids', []), 'claim_statuses' => $request->input('claim_statuses'), 'from_date' => $request->input('from_date'), 'to_date' => $request->input('to_date'), 'date_type' => $request->input('date_type', 'billDate'), 'policy_ids' => $request->input('policy_ids', []), 'vehicle_types' => $request->input('vehicle_types', []), 'wheeler_type' => $request->input('wheeler_type'), 'claim_filter_type' => $request->input('claim_filter_type'),];
            $year_id = session('year_id');
            $table = ExpenseClaim::tableName($year_id);
            $countQuery = $this->buildClaimQuery($filters, $table, true);
            $totalRecords = $countQuery->get()->count();
            $query = $this->buildClaimQuery($filters, $table);
            return DataTables::of($query)->with('recordsTotal', $totalRecords)->with('recordsFiltered', $totalRecords)->addIndexColumn()->editColumn('ClaimStatus', function ($row) {
                $badgeClass = 'bg-secondary-subtle text-secondary';
                $statusText = ucfirst($row->ClaimStatus);
                switch (strtolower($row->ClaimStatus)) {
                    case 'deactivate':
                        $badgeClass = 'bg-dark-subtle text-dark';
                        break;
                    case 'draft':
                    case 'submitted':
                        $badgeClass = 'bg-warning-subtle text-warning';
                        break;
                    case 'filled':
                        $badgeClass = 'bg-info-subtle text-info';
                        break;
                    case 'verified':
                        $badgeClass = 'bg-primary-subtle text-primary';
                        break;
                    case 'approved':
                        $badgeClass = 'bg-success-subtle text-success';
                        break;
                    case 'financed':
                        $badgeClass = 'bg-teal-subtle text-teal';
                        break;
                }
                return '<span style="width: 60px;" class="badge ' . $badgeClass . ' badge-border">' . $statusText . '</span>';
            })->editColumn('CrDate', function ($row) {
                return $row->CrDate ? Carbon::parse($row->CrDate)->format('d-m-Y') : '';
            })->editColumn('BillDate', function ($row) {
                return $row->BillDate ? Carbon::parse($row->BillDate)->format('d-m-Y') : '';
            })->editColumn('ClaimMonth', function ($row) {
                return $row->ClaimMonth ? Carbon::create()->month($row->ClaimMonth)->format('F') : '';
            })->addColumn('action', function ($row) {
                $dropdownId = 'dropdownMenuLink' . $row->ExpId;
                $html = '
                    <div class="dropdown">
                        <a href="#" role="button" id="' . $dropdownId . '" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ri-more-2-fill"></i>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="' . $dropdownId . '">
                            <li>
                                <a class="dropdown-item view-claim" href="#" 
                                data-bs-toggle="modal" 
                                data-bs-target="#claimDetailModal" 
                                data-claim-id="' . $row->ClaimId . '" 
                                data-expid="' . $row->ExpId . '">
                                View
                                </a>
                            </li>';
                if (strtolower($row->ClaimStatus) === 'deactivate') {
                    $html .= '
                            <li>
                                <a class="dropdown-item return-claim" href="#" 
                                data-claim-id="' . $row->ClaimId . '" 
                                data-expid="' . $row->ExpId . '">
                                Return
                                </a>
                            </li>';
                }
                $html .= '</ul></div>';
                return $html;
            })->rawColumns(['ClaimStatus', 'action'])->make(true);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not load claims: ' . $e->getMessage()], 500);
        }
    }
    public function export(Request $request)
    {
        ini_set('max_execution_time', 10000);
        ini_set('memory_limit', '5120M');
        $filters = ['function_ids' => $request->input('function_ids', []), 'vertical_ids' => $request->input('vertical_ids', []), 'department_ids' => $request->input('department_ids', []), 'sub_department_ids' => $request->input('sub_department_ids', []), 'user_ids' => $request->input('user_ids', []), 'months' => $request->input('months', []), 'claim_type_ids' => $request->input('claim_type_ids', []), 'claim_statuses' => $request->input('claim_statuses'), 'from_date' => $request->input('from_date'), 'to_date' => $request->input('to_date'), 'date_type' => $request->input('date_type', 'billDate'), 'policy_ids' => $request->input('policy_ids', []), 'vehicle_types' => $request->input('vehicle_types', []), 'wheeler_type' => $request->input('wheeler_type'), 'claim_filter_type' => $request->input('claim_filter_type'),];
        $year_id = session('year_id');
        $columns = $request->input('columns', []);
        $reportType = $request->input('reportType', 'general');
        $protectSheets = $request->boolean('protectSheets', false);
        $table = ExpenseClaim::tableName($year_id);
        if (empty($columns)) {
            return response()->json(['status' => 'error', 'message' => 'Please select at least one column to export'], 422);
        }
        try {
            $query = $this->buildClaimQuery($filters, $table, false, $columns);
            if (!$query->exists()) {
                return response()->json(['status' => 'error', 'message' => 'No claims found for the given filters'], 404);
            }
            $export = match ($reportType) {
                'month_wise' => new MonthWiseClaimReportExport($query, $filters, $columns, $protectSheets, $table),
                'department_wise' => new DepartmentWiseClaimReportExport($query, $filters, $columns, $protectSheets, $table),
                'claim_type_wise' => new ClaimTypeWiseClaimReportExport($query, $filters, $columns, $protectSheets, $table),
                'head_wise' => new HeadWiseClaimReport($query, $filters, $columns, 'Claims', $protectSheets, $table),
                default => new ClaimReportExport($query, $filters, $columns, 'Claims', $protectSheets, $table),
            };
            $reportName = match ($reportType) {
                'month_wise' => 'MonthWise_Claims',
                'department_wise' => 'DepartmentWise_Claims',
                'claim_type_wise' => 'ClaimTypeWise_Claims',
                'head_wise' => 'Headwise_Claim',
                default => 'General_Claims',
            };
            $fileName = sprintf('%s_%s.xlsx', $reportName, date('Ymd_His'));
            return Excel::download($export, $fileName);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Could not export claims', 'details' => $e->getMessage()], 500);
        }
    }
    public function sameDayClaimUpload()
    {
        return Excel::download(new SameDayUploadClaimExport('y7_expenseclaims'), 'SameDayUpload.xlsx');
    }
    public function getDailyActivityData(Request $request)
    {
        $fromDate = $request->input('fromDate');
        $toDate = $request->input('toDate');
        $year_id = session('year_id');
        $table = ExpenseClaim::tableName($year_id);
        $baseQuery = DB::table("{$table} as e")->where('e.ClaimStatus', '!=', 'Deactivate')->whereNotIn('e.ClaimId', [19, 20, 21]);
        $uploadQuery = (clone $baseQuery)->selectRaw('DATE(e.CrDate) AS ActionDate, COUNT(*) AS TotalUpload, 0 AS Punching, 0 AS Verified, 0 AS Approved, 0 AS Financed')->where('e.CrDate', '!=', '0000-00-00')->whereBetween(DB::raw('DATE(e.CrDate)'), [$fromDate, $toDate])->groupBy(DB::raw('DATE(e.CrDate)'));
        $punchingQuery = (clone $baseQuery)->selectRaw('DATE(e.FilledDate) AS ActionDate, 0 AS TotalUpload, COUNT(*) AS Punching, 0 AS Verified, 0 AS Approved, 0 AS Financed')->where('e.FilledDate', '!=', '0000-00-00')->whereBetween(DB::raw('DATE(e.FilledDate)'), [$fromDate, $toDate])->groupBy(DB::raw('DATE(e.FilledDate)'));
        $verifiedQuery = (clone $baseQuery)->selectRaw('DATE(e.VerifyDate) AS ActionDate, 0 AS TotalUpload, 0 AS Punching, COUNT(*) AS Verified, 0 AS Approved, 0 AS Financed')->where('e.VerifyDate', '!=', '0000-00-00')->whereBetween(DB::raw('DATE(e.VerifyDate)'), [$fromDate, $toDate])->groupBy(DB::raw('DATE(e.VerifyDate)'));
        $approvedQuery = (clone $baseQuery)->selectRaw('DATE(e.ApprDate) AS ActionDate, 0 AS TotalUpload, 0 AS Punching, 0 AS Verified, COUNT(*) AS Approved, 0 AS Financed')->where('e.ApprDate', '!=', '0000-00-00')->whereBetween(DB::raw('DATE(e.ApprDate)'), [$fromDate, $toDate])->groupBy(DB::raw('DATE(e.ApprDate)'));
        $financedQuery = (clone $baseQuery)->selectRaw('DATE(e.FinancedDate) AS ActionDate, 0 AS TotalUpload, 0 AS Punching, 0 AS Verified, 0 AS Approved, COUNT(*) AS Financed')->where('e.FinancedDate', '!=', '0000-00-00')->whereBetween(DB::raw('DATE(e.FinancedDate)'), [$fromDate, $toDate])->groupBy(DB::raw('DATE(e.FinancedDate)'));
        $unionQuery = $uploadQuery->unionAll($punchingQuery)->unionAll($verifiedQuery)->unionAll($approvedQuery)->unionAll($financedQuery);
        $finalQuery = DB::table(DB::raw("({$unionQuery->toSql()}) as sub"))->mergeBindings($unionQuery)->selectRaw('ActionDate, SUM(TotalUpload) AS TotalUpload, SUM(Punching) AS Punching, SUM(Verified) AS Verified, SUM(Approved) AS Approved, SUM(Financed) AS Financed')->groupBy('ActionDate')->orderBy('ActionDate')->get();
        $formattedData = $finalQuery->map(function ($row) {
            return ['ActionDate' => $row->ActionDate, 'TotalUpload' => $row->TotalUpload, 'Punching' => $row->Punching, 'Verified' => $row->Verified, 'Approved' => $row->Approved, 'Financed' => $row->Financed];
        })->toArray();
        return $this->jsonSuccess($formattedData, 'Daily activity data loaded successfully.');
    }
    public function exportDailyActivity(Request $request)
    {
        $fromDate = $request->input('fromDate');
        $toDate = $request->input('toDate');
        return Excel::download(new DailyActivityReportExport($fromDate, $toDate), 'daily_activity_report_' . $fromDate . '_to_' . $toDate . '.xlsx');
    }
    public function returnClaim(Request $request)
    {
        $request->validate(['expid' => 'required|integer', 'claim_id' => 'required|integer']);
        $year_id = session('year_id');
        $table = ExpenseClaim::tableName($year_id);
        $updated = DB::table($table)->where('ExpId', $request->expid)->update(['ClaimStatus' => 'Submitted', 'ClaimAtStep' => 2, 'RtnBy' => Auth::id()]);
        if ($updated) {
            return $this->jsonSuccess([], 'Claim returned successfully.');
        } else {
            return $this->jsonError('No record updated.');
        }
    }
}
