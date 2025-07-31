<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\ExpenseClaim;
use Illuminate\Support\Collection;
class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index(Request $request)
    {
        // Return the view without data initially
        return view('home');
    }
    public function getDashboardData(Request $request)
    {
        $yearId = (int) session('year_id');
        if ($yearId <= 0) {
            return response()->json(['error' => 'Invalid year ID in session.', 'cardData' => [], 'claimTypeTotals' => collect([['ClaimName' => 'No Data', 'TotalFinancedAmount' => 0]]), 'monthlyTotals' => collect([]), 'departmentTotals' => collect([['department_name' => 'No Data', "TotalFinancedTAmt_Y{$yearId}" => 0, "TotalFinancedTAmt_Y" . ($yearId - 1) => 0]]), 'yearlyComparison' => null, 'totalAllMonths' => ['filled' => 0, 'verified' => 0, 'approved' => 0, 'financed' => 0]], 400);
        }
        $previousYearId = $yearId - 1;
        $tableName = ExpenseClaim::tableName();
        $previousYearTable = "y{$previousYearId}_expenseclaims";
        if (!DB::getSchemaBuilder()->hasTable($previousYearTable)) {
            $previousYearTable = $tableName;
        }
        $claimStatusCounts = DB::table($tableName)->selectRaw("IFNULL(LOWER(ClaimStatus), 'total') as ClaimStatus, COUNT(*) as TotalCount")->groupByRaw('ClaimStatus WITH ROLLUP')->get();
        $claimTypeTotals = DB::table($tableName . ' as e')
            ->join('claimtype as ct', 'ct.ClaimId', '=', 'e.ClaimId')
            ->select(
                'ct.ClaimName',
                'ct.ClaimCode',
                DB::raw("SUM(CASE WHEN e.FilledBy > 0 AND e.FilledDate != '0000-00-00' THEN e.FilledTAmt ELSE 0 END) AS FilledTotal"),
                DB::raw("SUM(CASE WHEN e.VerifyBy > 0 AND e.VerifyDate != '0000-00-00' THEN e.VerifyTAmt ELSE 0 END) AS VerifiedTotal"),
                DB::raw("SUM(CASE WHEN e.ApprBy > 0 AND e.ApprDate != '0000-00-00' THEN e.ApprTAmt ELSE 0 END) AS ApprovedTotal"),
                DB::raw("SUM(CASE WHEN e.FinancedBy > 0 AND e.FinancedDate != '0000-00-00' THEN e.FinancedTAmt ELSE 0 END) AS FinancedTotal")
            )
            ->where('ct.ClaimStatus', 'A')
            ->whereIn('ct.cgId', ['1', '7'])
            ->whereNotIn('e.ClaimStatus', ['Draft', 'Submitted'])
            ->groupBy('ct.ClaimId', 'ct.ClaimName', 'ct.ClaimCode')
            ->orderBy('ct.cgId')
            ->orderBy('ct.ClaimName', 'asc')
            ->get();
        $monthlyStatusTotals = DB::table($tableName)->selectRaw("
                ELT(ClaimMonth, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December') as MonthName,
                SUM(CASE WHEN FilledBy > 0 AND FilledDate != '0000-00-00' THEN FilledTAmt ELSE 0 END) AS FilledTotal,
                SUM(CASE WHEN VerifyBy > 0 AND VerifyDate != '0000-00-00' THEN VerifyTAmt ELSE 0 END) AS VerifiedTotal,
                SUM(CASE WHEN ApprBy > 0 AND ApprDate != '0000-00-00' THEN ApprTAmt ELSE 0 END) AS ApprovedTotal,
                SUM(CASE WHEN FinancedBy > 0 AND FinancedDate != '0000-00-00' THEN FinancedTAmt ELSE 0 END) AS FinancedTotal,
                CASE WHEN ClaimMonth >= 4 THEN ClaimMonth ELSE ClaimMonth + 12 END AS FiscalOrder
            ")->whereBetween('ClaimMonth', [1, 12])->whereNotIn('ClaimStatus', ['Draft', 'Submitted'])->groupBy('ClaimMonth')->orderBy('FiscalOrder')->get();
        $monthlyTotals = DB::table($tableName)->select('ClaimMonth', DB::raw('
                SUM(CASE WHEN FilledBy > 0 AND FilledDate != "0000-00-00" THEN FilledTAmt ELSE 0 END) AS FilledTotal,
                SUM(CASE WHEN VerifyBy > 0 AND VerifyDate != "0000-00-00" THEN VerifyTAmt ELSE 0 END) AS VerifiedTotal,
                SUM(CASE WHEN ApprBy > 0 AND ApprDate != "0000-00-00" THEN ApprTAmt ELSE 0 END) AS ApprovedTotal,
                SUM(CASE WHEN FinancedBy > 0 AND FinancedDate != "0000-00-00" THEN FinancedTAmt ELSE 0 END) AS FinancedTotal
            '))->where('ClaimMonth', '!=', 0)->whereNotIn('ClaimStatus', ['Draft', 'Submitted'])->groupBy('ClaimMonth')->orderBy('ClaimMonth')->get();
        $totalAllMonths = ['filled' => $monthlyTotals->sum('FilledTotal'), 'verified' => $monthlyTotals->sum('VerifiedTotal'), 'approved' => $monthlyTotals->sum('ApprovedTotal'), 'financed' => $monthlyTotals->sum('FinancedTotal'),];
        $departmentTotals = DB::table(DB::raw("(SELECT ExpId, CrBy, ClaimYearId, FinancedTAmt, ClaimStatus FROM {$previousYearTable} 
                                        UNION ALL 
                                        SELECT ExpId, CrBy, ClaimYearId, FinancedTAmt, ClaimStatus FROM {$tableName}) as e"))->join('hrims.hrm_employee_general as gen', 'gen.EmployeeID', '=', 'e.CrBy')->join('hrims.core_departments as dep', 'gen.DepartmentId', '=', 'dep.id')->whereNotNull('dep.department_name')->whereNotIn('e.ClaimStatus', ['Draft', 'Submitted'])->groupBy('dep.department_name', 'dep.department_code')->select('dep.department_name', 'dep.department_code', DB::raw("SUM(CASE WHEN e.ClaimYearId = {$yearId} THEN e.FinancedTAmt ELSE 0 END) as TotalFinancedTAmt_Y{$yearId}"), DB::raw("SUM(CASE WHEN e.ClaimYearId = {$previousYearId} THEN e.FinancedTAmt ELSE 0 END) as TotalFinancedTAmt_Y{$previousYearId}"), DB::raw("
                    ROUND(
                        CASE 
                            WHEN SUM(CASE WHEN e.ClaimYearId = {$previousYearId} THEN e.FinancedTAmt ELSE 0 END) = 0 THEN NULL
                            ELSE (
                                (
                                    SUM(CASE WHEN e.ClaimYearId = {$yearId} THEN e.FinancedTAmt ELSE 0 END) -
                                    SUM(CASE WHEN e.ClaimYearId = {$previousYearId} THEN e.FinancedTAmt ELSE 0 END)
                                ) / SUM(CASE WHEN e.ClaimYearId = {$previousYearId} THEN e.FinancedTAmt ELSE 0 END)
                            ) * 100
                        END
                    , 2) as VariationPercentage
                "))->orderBy('dep.department_name')->get();
        $yearlyComparison = DB::table(DB::raw("(SELECT ClaimYearId, FinancedTAmt FROM {$previousYearTable} UNION ALL SELECT ClaimYearId, FinancedTAmt FROM {$tableName}) as e"))->selectRaw("
                SUM(CASE WHEN ClaimYearId = {$yearId} THEN FinancedTAmt ELSE 0 END) as CY_Expense,
                SUM(CASE WHEN ClaimYearId = {$previousYearId} THEN FinancedTAmt ELSE 0 END) as PY_Expense,
                SUM(CASE WHEN ClaimYearId = {$yearId} THEN FinancedTAmt ELSE 0 END) - SUM(CASE WHEN ClaimYearId = {$previousYearId} THEN FinancedTAmt ELSE 0 END) as Variance,
                (SUM(CASE WHEN ClaimYearId = {$yearId} THEN FinancedTAmt ELSE 0 END) - SUM(CASE WHEN ClaimYearId = {$previousYearId} THEN FinancedTAmt ELSE 0 END)) / NULLIF(SUM(CASE WHEN ClaimYearId = {$previousYearId} THEN FinancedTAmt ELSE 0 END), 0) * 100 as Variance_Percentage
            ")->whereIn('ClaimYearId', [$previousYearId, $yearId])->first();
        $statusMap = ['draft' => 'Draft', 'deactivate' => 'Deactivate', 'submitted' => 'Submitted', 'filled' => 'Filled', 'verified' => 'Verified', 'approved' => 'Approved', 'financed' => 'Financed', 'total' => 'Total Expense'];
        $cardData = [];
        foreach ($claimStatusCounts as $status) {
            $key = strtolower($status->ClaimStatus);
            if (isset($statusMap[$key])) {
                $cardData[$statusMap[$key]] = $status->TotalCount;
            }
        }
        $claimTypeTotals = $claimTypeTotals->isEmpty()
            ? collect([
                [
                    'ClaimName' => 'No Data',
                    'ClaimCode' => '',
                    'FilledTotal' => 0,
                    'VerifiedTotal' => 0,
                    'ApprovedTotal' => 0,
                    'FinancedTotal' => 0,
                ]
            ])
            : $claimTypeTotals;
        $departmentTotals = $departmentTotals->isEmpty() ? collect([['department_name' => 'No Data', "TotalFinancedTAmt_Y{$yearId}" => 0, "TotalFinancedTAmt_Y{$previousYearId}" => 0, 'VariationPercentage' => 0]]) : $departmentTotals;
        return response()->json(['cardData' => $cardData, 'claimTypeTotals' => $claimTypeTotals, 'monthlyTotals' => $monthlyStatusTotals, 'totalAllMonths' => $totalAllMonths, 'departmentTotals' => $departmentTotals, 'yearlyComparison' => $yearlyComparison, 'yearId' => $yearId]);
    }
}
