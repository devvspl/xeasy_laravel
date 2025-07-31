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
    // Retrieve the year ID from the session and cast it to an integer
    public function index(Request $request)
    {
        // Store the year ID from the session
        $yearId = (int) session('year_id');

        // Check if the year ID is invalid (less than or equal to 0)
        if ($yearId <= 0) {
            // Return an error response with default empty data structures
            return ['error' => 'Invalid year ID in session.', 'cardData' => [], 'claimTypeTotals' => collect([['ClaimName' => 'No Data', 'TotalFinancedAmount' => 0]]), 'monthlyTotals' => collect([]), 'departmentTotals' => collect([['department_name' => 'No Data', "TotalFinancedTAmt_Y{$yearId}" => 0, "TotalFinancedTAmt_Y" . ($yearId - 1) => 0]]), 'yearlyComparison' => null];
        }

        // Calculate the previous year ID
        $previousYearId = $yearId - 1;

        // Get the table name for expense claims
        $tableName = ExpenseClaim::tableName();

        // Define the table name for the previous year's expense claims
        $previousYearTable = "y{$previousYearId}_expenseclaims";

        // Check if the previous year's table exists; if not, use the current table
        if (!DB::getSchemaBuilder()->hasTable($previousYearTable)) {
            $previousYearTable = $tableName;
        }

        // Query to get the count of claims by status, including a total count using ROLLUP
        $claimStatusCounts = DB::table($tableName)->selectRaw("IFNULL(LOWER(ClaimStatus), 'total') as ClaimStatus, COUNT(*) as TotalCount")->groupByRaw('ClaimStatus WITH ROLLUP')->get();

        // Query to get the total financed amount by claim type, joining with the claimtype table
        $claimTypeTotals = DB::table($tableName)->join('claimtype', 'claimtype.ClaimId', '=', "$tableName.ClaimId")->select('claimtype.ClaimName', DB::raw("SUM($tableName.FinancedTAmt) as TotalFinancedAmount"))->groupBy('claimtype.ClaimId', 'claimtype.ClaimName')->get();

        // Query to get monthly totals for filled, verified, approved, and financed amounts, excluding Draft and Submitted statuses
        $monthlyStatusTotals = DB::table($tableName)->selectRaw("ELT(ClaimMonth, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December') as MonthName,SUM(CASE WHEN FilledBy > 0 AND FilledDate != '0000-00-00' THEN FilledTAmt ELSE 0 END) AS FilledTotal,SUM(CASE WHEN VerifyBy > 0 AND VerifyDate != '0000-00-00' THEN VerifyTAmt ELSE 0 END) AS VerifiedTotal,SUM(CASE WHEN ApprBy > 0 AND ApprDate != '0000-00-00' THEN ApprTAmt ELSE 0 END) AS ApprovedTotal,SUM(CASE WHEN FinancedBy > 0 AND FinancedDate != '0000-00-00' THEN FinancedTAmt ELSE 0 END) AS FinancedTotal,CASE WHEN ClaimMonth >= 4 THEN ClaimMonth ELSE ClaimMonth + 12 END AS FiscalOrder")->whereBetween('ClaimMonth', [1, 12])->whereNotIn('ClaimStatus', ['Draft', 'Submitted'])->groupBy('ClaimMonth')->orderBy('FiscalOrder')->get();

        // Query to get monthly totals for filled, verified, approved, and financed amounts, excluding Draft and Submitted statuses, ordered by ClaimMonth
        $monthlyTotals = DB::table($tableName)->select('ClaimMonth', DB::raw('SUM(CASE WHEN FilledBy > 0 AND FilledDate != "0000-00-00" THEN FilledTAmt ELSE 0 END) AS FilledTotal,SUM(CASE WHEN VerifyBy > 0 AND VerifyDate != "0000-00-00" THEN VerifyTAmt ELSE 0 END) AS VerifiedTotal,SUM(CASE WHEN ApprBy > 0 AND ApprDate != "0000-00-00" THEN ApprTAmt ELSE 0 END) AS ApprovedTotal,SUM(CASE WHEN FinancedBy > 0 AND FinancedDate != "0000-00-00" THEN FinancedTAmt ELSE 0 END) AS FinancedTotal'))->where('ClaimMonth', '!=', 0)->whereNotIn('ClaimStatus', ['Draft', 'Submitted'])->groupBy('ClaimMonth')->orderBy('ClaimMonth')->get();

        // Calculate the total sums for filled, verified, approved, and financed amounts across all months
        $totalAllMonths = ['filled' => $monthlyTotals->sum('FilledTotal'), 'verified' => $monthlyTotals->sum('VerifiedTotal'), 'approved' => $monthlyTotals->sum('ApprovedTotal'), 'financed' => $monthlyTotals->sum('FinancedTotal'),];

        // Query to get department-wise financed amounts for the current and previous years, including variation percentage
        $departmentTotals = DB::table(DB::raw("(SELECT ExpId, CrBy, ClaimYearId, FinancedTAmt, ClaimStatus FROM {$previousYearTable} 
                                        UNION ALL 
                                        SELECT ExpId, CrBy, ClaimYearId, FinancedTAmt, ClaimStatus FROM {$tableName}) as e"))->join('hrims.hrm_employee_general as gen', 'gen.EmployeeID', '=', 'e.CrBy')->join('hrims.core_departments as dep', 'gen.DepartmentId', '=', 'dep.id')->whereNotNull('dep.department_name')->whereNotIn('e.ClaimStatus', ['Draft', 'Submitted'])->groupBy('dep.department_name')->select('dep.department_name', DB::raw("SUM(CASE WHEN e.ClaimYearId = {$yearId} THEN e.FinancedTAmt ELSE 0 END) as TotalFinancedTAmt_Y{$yearId}"), DB::raw("SUM(CASE WHEN e.ClaimYearId = {$previousYearId} THEN e.FinancedTAmt ELSE 0 END) as TotalFinancedTAmt_Y{$previousYearId}"), DB::raw("
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

        // Query to compare total financed amounts between the current and previous years, including variance and percentage
        $yearlyComparison = DB::table(DB::raw("(SELECT ClaimYearId, FinancedTAmt FROM {$previousYearTable} UNION ALL SELECT ClaimYearId, FinancedTAmt FROM {$tableName}) as e"))->selectRaw("SUM(CASE WHEN ClaimYearId = {$yearId} THEN FinancedTAmt ELSE 0 END) as CY_Expense, SUM(CASE WHEN ClaimYearId = {$previousYearId} THEN FinancedTAmt ELSE 0 END) as PY_Expense, SUM(CASE WHEN ClaimYearId = {$yearId} THEN FinancedTAmt ELSE 0 END) - SUM(CASE WHEN ClaimYearId = {$previousYearId} THEN FinancedTAmt ELSE 0 END) as Variance, (SUM(CASE WHEN ClaimYearId = {$yearId} THEN FinancedTAmt ELSE 0 END) - SUM(CASE WHEN ClaimYearId = {$previousYearId} THEN FinancedTAmt ELSE 0 END)) / NULLIF(SUM(CASE WHEN ClaimYearId = {$previousYearId} THEN FinancedTAmt ELSE 0 END), 0) * 100 as Variance_Percentage")->whereIn('ClaimYearId', [$previousYearId, $yearId])->first();

        // Define a mapping for claim status display names
        $statusMap = ['draft' => 'Draft', 'deactivate' => 'Deactivate', 'submitted' => 'Submitted', 'filled' => 'Filled', 'verified' => 'Verified', 'approved' => 'Approved', 'financed' => 'Financed', 'total' => 'Total Expense'];

        // Initialize an array to store claim status counts for display
        $cardData = [];

        // Map claim status counts to their display names
        foreach ($claimStatusCounts as $status) {
            $key = strtolower($status->ClaimStatus);
            if (isset($statusMap[$key])) {
                $cardData[$statusMap[$key]] = $status->TotalCount;
            }
        }

        // Set default data for claimTypeTotals if the result is empty
        $claimTypeTotals = $claimTypeTotals->isEmpty() ? collect([['ClaimName' => 'No Data', 'TotalFinancedAmount' => 0]]) : $claimTypeTotals;

        // Set default data for departmentTotals if the result is empty
        $departmentTotals = $departmentTotals->isEmpty() ? collect([['department_name' => 'No Data', "TotalFinancedTAmt_Y{$yearId}" => 0, "TotalFinancedTAmt_Y{$previousYearId}" => 0, 'VariationPercentage' => 0]]) : $departmentTotals;

        // Compile all data into a single array for debugging
        $data = ['cardData' => $cardData, 'claimTypeTotals' => $claimTypeTotals, 'monthlyTotals' => $monthlyStatusTotals, 'totalAllMonths' => $totalAllMonths, 'departmentTotals' => $departmentTotals, 'yearlyComparison' => $yearlyComparison];

        // Dump the data for debugging purposes
        // dd($data);

        // Return the view with all compiled data
        return view('home', compact('cardData', 'claimTypeTotals', 'monthlyStatusTotals', 'totalAllMonths', 'departmentTotals', 'yearlyComparison'));
    }
}
