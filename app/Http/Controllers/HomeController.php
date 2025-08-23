<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        return view('home');
    }

    public function getDashboardData(Request $request)
    {
        try {
            $startDate = $request->input('bill_date_from');
            $startDate = Carbon::parse($startDate)->addDay()->toDateString();
            $endDate = $request->input('bill_date_to');
            $yearId = (int) session('year_id');
            if ($yearId <= 0) {
                return response()->json([
                    'error' => 'Invalid year ID in session.',
                    'cardData' => [],
                    'claimTypeTotals' => collect([['ClaimName' => 'No Data', 'TotalFinancedAmount' => 0]]),
                    'monthlyTotals' => collect([]),
                    'departmentTotals' => collect([
                        [
                            'department_name' => 'No Data',
                            "TotalFinancedTAmt_Y{$yearId}" => 0,
                            "TotalFinancedTAmt_Y" . ($yearId - 1) => 0
                        ]
                    ]),
                    'top10TravelersSplitByWType' => [],
                    'yearlyComparison' => null,
                    'totalAllMonths' => ['filled' => 0, 'verified' => 0, 'approved' => 0, 'financed' => 0]
                ], 400);
            }
            $previousYearStartDate = Carbon::parse($startDate)->subYear()->toDateString();
            $previousYearEndDate = Carbon::parse($endDate)->subYear()->toDateString();
            $previousYearId = $yearId - 1;
            $tableName = "y{$yearId}_expenseclaims";
            $previousYearTable = "y{$previousYearId}_expenseclaims";
            if (!DB::getSchemaBuilder()->hasTable($previousYearTable)) {
                $previousYearTable = $tableName;
            }


            $claimStatusCounts = $this->getClaimStatusCounts($tableName, $startDate, $endDate);
            $claimTypeTotals = $this->getClaimTypeTotals($tableName, $startDate, $endDate);
            $monthlyStatusTotals = $this->getMonthlyStatusTotals($tableName, $startDate, $endDate);
            $monthlyTotals = $this->getRawMonthlyTotals($tableName, $startDate, $endDate);
            $top10TravelersSplitByWType = $this->getTop10TravelersSplitByWType($tableName, $startDate, $endDate);
            $departmentTotals = $this->getDepartmentTotals($tableName, $previousYearTable, $yearId, $previousYearId, $startDate, $endDate, $previousYearStartDate, $previousYearEndDate);
            $topEmployees = $this->getTopEmployees($tableName, $startDate, $endDate);
            $yearlyComparison = $this->getYearlyComparison($tableName, $previousYearTable, $yearId, $previousYearId, $startDate, $endDate, $previousYearStartDate, $previousYearEndDate);
            $totalAllMonths = [
                'filled' => $monthlyTotals->sum('FilledTotal'),
                'verified' => $monthlyTotals->sum('VerifiedTotal'),
                'approved' => $monthlyTotals->sum('ApprovedTotal'),
                'financed' => $monthlyTotals->sum('FinancedTotal')
            ];

            $statusMap = [
                'draft' => 'Draft',
                'deactivate' => 'Deactivate',
                'submitted' => 'Submitted',
                'filled' => 'Filled',
                'verified' => 'Verified',
                'approved' => 'Approved',
                'financed' => 'Financed',
                'total' => 'Total Expense'
            ];

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

            $departmentTotals = $departmentTotals->isEmpty()
                ? collect([
                    [
                        'department_name' => 'No Data',
                        "TotalFinancedTAmt_Y{$yearId}" => 0,
                        "TotalFinancedTAmt_Y{$previousYearId}" => 0,
                        'VariationPercentage' => 0
                    ]
                ])
                : $departmentTotals;

            return response()->json([
                'cardData' => $cardData,
                'claimTypeTotals' => $claimTypeTotals,
                'monthlyTotals' => $monthlyStatusTotals,
                'totalAllMonths' => $totalAllMonths,
                'departmentTotals' => $departmentTotals,
                'top10TravelersSplitByWType' => $top10TravelersSplitByWType,
                'yearlyComparison' => $yearlyComparison,
                'topEmployees' => $topEmployees,
                'yearId' => $yearId
            ]);
        } catch (\Exception $e) {
            dd('Error in getDashboardData: ' . $e->getMessage(), [
                'bill_date_from' => $startDate ?? 'N/A',
                'bill_date_to' => $endDate ?? 'N/A',
                'year_id' => session('year_id') ?? 'N/A',
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Server error occurred'], 500);
        }
    }

    function getClaimStatusCounts($tableName, $startDate, $endDate)
    {
        return DB::table($tableName)
            ->selectRaw("IFNULL(LOWER(ClaimStatus), 'total') as ClaimStatus, COUNT(*) as TotalCount")
            ->where('BillDate', '!=', '0000-00-00')
            ->whereNotNull('BillDate')
            ->whereBetween('BillDate', [$startDate, $endDate])
            ->groupByRaw('ClaimStatus WITH ROLLUP')
            ->get();
    }

    function getClaimTypeTotals($tableName, $startDate, $endDate)
    {
        return DB::table("{$tableName} as e")
            ->join('claimtype as ct', 'ct.ClaimId', '=', 'e.ClaimId')
            ->select(
                'ct.ClaimName',
                'ct.ClaimCode',
                DB::raw("SUM(CASE WHEN e.FilledBy > 0 AND e.FilledDate != '0000-00-00' THEN e.FilledTAmt ELSE 0 END) AS FilledTotal"),
                DB::raw("SUM(CASE WHEN e.VerifyBy > 0 AND e.VerifyDate != '0000-00-00' THEN e.VerifyTAmt ELSE 0 END) AS VerifiedTotal"),
                DB::raw("SUM(CASE WHEN e.ApprBy > 0 AND e.ApprDate != '0000-00-00' THEN e.ApprTAmt ELSE 0 END) AS ApprovedTotal"),
                DB::raw("SUM(CASE WHEN e.FinancedBy > 0 AND e.FinancedDate != '0000-00-00' THEN e.FinancedTAmt ELSE 0 END) AS FinancedTotal")
            )
            ->whereNotIn('e.ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
            ->where('e.BillDate', '!=', '0000-00-00')
            ->whereNotNull('e.BillDate')
            ->whereBetween('e.BillDate', [$startDate, $endDate])
            ->groupBy('ct.ClaimId', 'ct.ClaimName', 'ct.ClaimCode')
            ->orderBy('ct.cgId')
            ->orderBy('ct.ClaimName', 'asc')
            ->get();
    }

    function getMonthlyStatusTotals($tableName, $startDate, $endDate)
    {
        return DB::table($tableName)
            ->selectRaw("
            ELT(ClaimMonth, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December') as MonthName,
            SUM(CASE WHEN FilledBy > 0 AND FilledDate != '0000-00-00' THEN FilledTAmt ELSE 0 END) AS FilledTotal,
            SUM(CASE WHEN VerifyBy > 0 AND VerifyDate != '0000-00-00' THEN VerifyTAmt ELSE 0 END) AS VerifiedTotal,
            SUM(CASE WHEN ApprBy > 0 AND ApprDate != '0000-00-00' THEN ApprTAmt ELSE 0 END) AS ApprovedTotal,
            SUM(CASE WHEN FinancedBy > 0 AND FinancedDate != '0000-00-00' THEN FinancedTAmt ELSE 0 END) AS FinancedTotal,
            CASE WHEN ClaimMonth >= 4 THEN ClaimMonth ELSE ClaimMonth + 12 END AS FiscalOrder
        ")
            ->whereBetween('BillDate', [$startDate, $endDate])
            ->where('BillDate', '!=', '0000-00-00')
            ->whereNotNull('BillDate')
            ->whereBetween('ClaimMonth', [1, 12])
            ->whereNotIn('ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
            ->groupBy('ClaimMonth')
            ->orderBy('FiscalOrder')
            ->get();
    }

    function getRawMonthlyTotals($tableName, $startDate, $endDate)
    {
        return DB::table($tableName)
            ->select('ClaimMonth', DB::raw('
            SUM(CASE WHEN FilledBy > 0 AND FilledDate != "0000-00-00" THEN FilledTAmt ELSE 0 END) AS FilledTotal,
            SUM(CASE WHEN VerifyBy > 0 AND VerifyDate != "0000-00-00" THEN VerifyTAmt ELSE 0 END) AS VerifiedTotal,
            SUM(CASE WHEN ApprBy > 0 AND ApprDate != "0000-00-00" THEN ApprTAmt ELSE 0 END) AS ApprovedTotal,
            SUM(CASE WHEN FinancedBy > 0 AND FinancedDate != "0000-00-00" THEN FinancedTAmt ELSE 0 END) AS FinancedTotal
        '))
            ->whereBetween('BillDate', [$startDate, $endDate])
            ->where('BillDate', '!=', '0000-00-00')
            ->whereNotNull('BillDate')
            ->where('ClaimMonth', '!=', 0)
            ->whereNotIn('ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
            ->groupBy('ClaimMonth')
            ->orderBy('ClaimMonth')
            ->get();
    }

    function getRawTotals(string $tableName, $startDate, $endDate)
    {
        return DB::table($tableName)
            ->selectRaw('
            SUM(CASE WHEN FilledBy > 0 AND FilledDate != "0000-00-00" THEN FilledTAmt ELSE 0 END) AS FilledTotal,
            SUM(CASE WHEN VerifyBy > 0 AND VerifyDate != "0000-00-00" THEN VerifyTAmt ELSE 0 END) AS VerifiedTotal,
            SUM(CASE WHEN ApprBy > 0 AND ApprDate != "0000-00-00" THEN ApprTAmt ELSE 0 END) AS ApprovedTotal,
            SUM(CASE WHEN FinancedBy > 0 AND FinancedDate != "0000-00-00" THEN FinancedTAmt ELSE 0 END) AS FinancedTotal
        ')
            ->whereBetween('BillDate', [$startDate, $endDate])
            ->where('BillDate', '!=', '0000-00-00')
            ->whereNotNull('BillDate')
            ->where('ClaimMonth', '!=', 0)
            ->whereNotIn('ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
            ->first();
    }

    function getDepartmentTotals($tableName, $previousYearTable, $yearId, $previousYearId, $startDate, $endDate, $previousYearStartDate, $previousYearEndDate)
    {
        return DB::table(DB::raw("
        (SELECT ExpId, CrBy, ClaimYearId, FinancedTAmt, ClaimStatus, BillDate, FinancedBy FROM {$previousYearTable}
         WHERE BillDate BETWEEN '{$previousYearStartDate}' AND '{$previousYearEndDate}'
         UNION ALL 
         SELECT ExpId, CrBy, ClaimYearId, FinancedTAmt, ClaimStatus, BillDate, FinancedBy FROM {$tableName}
         WHERE BillDate BETWEEN '{$startDate}' AND '{$endDate}') as e"))
            ->leftJoin('hrims.hrm_employee_general as gen', 'gen.EmployeeID', '=', 'e.CrBy')
            ->leftJoin('hrims.core_departments as dep', 'gen.DepartmentId', '=', 'dep.id')
            ->whereNotIn('e.ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
            ->where('e.BillDate', '!=', '0000-00-00')
            ->whereNotNull('e.BillDate')
            ->where('e.FinancedBy', '!=', '0')
            ->groupBy('dep.department_name', 'dep.department_code')
            ->select(
                'dep.department_name',
                'dep.department_code',
                DB::raw("SUM(CASE WHEN e.ClaimYearId = {$yearId} THEN e.FinancedTAmt ELSE 0 END) as TotalFinancedTAmt_Y{$yearId}"),
                DB::raw("SUM(CASE WHEN e.ClaimYearId = {$previousYearId} THEN e.FinancedTAmt ELSE 0 END) as TotalFinancedTAmt_Y{$previousYearId}"),
                DB::raw("
                ROUND(
                    CASE 
                        WHEN SUM(CASE WHEN e.ClaimYearId = {$previousYearId} THEN e.FinancedTAmt ELSE 0 END) = 0 THEN NULL
                        ELSE (
                            (
                                SUM(CASE WHEN e.ClaimYearId = {$yearId} THEN e.FinancedTAmt ELSE 0 END) -
                                SUM(CASE WHEN e.ClaimYearId = {$previousYearId} THEN e.FinancedTAmt ELSE 0 END)
                            ) / SUM(CASE WHEN e.ClaimYearId = {$previousYearId} THEN e.FinancedTAmt ELSE 0 END)
                        ) * 100
                    END, 2
                ) as VariationPercentage
            ")
            )
            ->orderBy('dep.department_name')
            ->get();
    }

    function getYearlyComparison($tableName, $previousYearTable, $yearId, $previousYearId, $startDate, $endDate, $previousYearStartDate, $previousYearEndDate)
    {
        $subQuery = DB::table($previousYearTable)
            ->select('ClaimYearId', 'FinancedTAmt', 'ClaimStatus', 'BillDate', 'FinancedBy')
            ->whereNotIn('ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
            ->whereBetween('BillDate', [$previousYearStartDate, $previousYearEndDate])
            ->where('BillDate', '!=', '0000-00-00')
            ->whereNotNull('BillDate')
            ->unionAll(
                DB::table($tableName)
                    ->select('ClaimYearId', 'FinancedTAmt', 'ClaimStatus', 'BillDate', 'FinancedBy')
                    ->whereNotIn('ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
                    ->whereBetween('BillDate', [$startDate, $endDate])
                    ->where('BillDate', '!=', '0000-00-00')
                    ->whereNotNull('BillDate')
            );

        return DB::query()
            ->fromSub($subQuery, 'e')
            ->selectRaw("
            SUM(CASE WHEN ClaimYearId = ? THEN FinancedTAmt ELSE 0 END) as CY_Expense,
            SUM(CASE WHEN ClaimYearId = ? THEN FinancedTAmt ELSE 0 END) as PY_Expense,
            SUM(CASE WHEN ClaimYearId = ? THEN FinancedTAmt ELSE 0 END) - SUM(CASE WHEN ClaimYearId = ? THEN FinancedTAmt ELSE 0 END) as Variance,
            (SUM(CASE WHEN ClaimYearId = ? THEN FinancedTAmt ELSE 0 END) - SUM(CASE WHEN ClaimYearId = ? THEN FinancedTAmt ELSE 0 END)) / NULLIF(SUM(CASE WHEN ClaimYearId = ? THEN FinancedTAmt ELSE 0 END), 0) * 100 as Variance_Percentage
        ", [
                $yearId,
                $previousYearId,
                $yearId,
                $previousYearId,
                $yearId,
                $previousYearId,
                $previousYearId
            ])
            ->whereIn('ClaimYearId', [$previousYearId, $yearId])
            ->where('BillDate', '!=', '0000-00-00')
            ->whereNotNull('BillDate')
            ->where('FinancedBy', '!=', '0')
            ->first();
    }

    function getTopEmployees($tableName, $startDate, $endDate, $limit = 10)
    {
        return DB::table("{$tableName} as e")
            ->join('hrims.hrm_employee as emp', 'emp.EmployeeID', '=', 'e.CrBy')
            ->join('hrims.hrm_employee_general as gen', 'gen.EmployeeID', '=', 'e.CrBy')
            ->leftJoin('hrims.core_departments as dep', 'gen.DepartmentId', '=', 'dep.id')
            ->whereBetween('e.BillDate', [$startDate, $endDate])
            ->where('e.BillDate', '!=', '0000-00-00')
            ->whereNotNull('e.BillDate')
            ->whereNotIn('e.ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
            ->groupBy('e.CrBy', 'emp.Fname', 'emp.Sname', 'emp.Lname', 'emp.EmpCode', 'dep.department_name')
            ->select('e.CrBy', DB::raw("CONCAT(emp.Fname, ' ', emp.Sname, ' ', emp.Lname) AS employee_name"), 'emp.EmpCode', 'dep.department_name', DB::raw('SUM(e.FilledTAmt) as filled_total_amount'), DB::raw('SUM(e.FinancedTAmt) as payment_total_amount'))
            ->orderByDesc('payment_total_amount')
            ->limit($limit)
            ->get();
    }

    public function getEmployeeTrend(Request $request)
    {
        $yearId = (int) session('year_id');
        $tableName = "y{$yearId}_expenseclaims";
        $startDate = Carbon::parse($request->input('bill_date_from'))->toDateString();
        $endDate = Carbon::parse($request->input('bill_date_to'))->toDateString();
        $employeeId = $request->input('employee_id');

        $data = DB::table("{$tableName} as e")
            ->join("claimtype as ct", "ct.ClaimId", "=", "e.ClaimId")
            ->select(
                "ct.ClaimName",
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 1  THEN e.FinancedTAmt ELSE 0 END) AS Jan"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 2  THEN e.FinancedTAmt ELSE 0 END) AS Feb"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 3  THEN e.FinancedTAmt ELSE 0 END) AS Mar"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 4  THEN e.FinancedTAmt ELSE 0 END) AS Apr"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 5  THEN e.FinancedTAmt ELSE 0 END) AS May"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 6  THEN e.FinancedTAmt ELSE 0 END) AS Jun"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 7  THEN e.FinancedTAmt ELSE 0 END) AS Jul"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 8  THEN e.FinancedTAmt ELSE 0 END) AS Aug"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 9  THEN e.FinancedTAmt ELSE 0 END) AS Sep"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 10 THEN e.FinancedTAmt ELSE 0 END) AS Oct"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 11 THEN e.FinancedTAmt ELSE 0 END) AS Nov"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 12 THEN e.FinancedTAmt ELSE 0 END) AS `Dec`"),
                DB::raw("SUM(e.FinancedTAmt) AS total_year")
            )
            ->whereBetween("e.BillDate", [$startDate, $endDate])
            ->whereNotNull("e.BillDate")
            ->where("e.BillDate", "!=", "0000-00-00")
            ->whereNotIn("e.ClaimStatus", ["Draft", "Submitted", "Deactivate"])
            ->where("e.CrBy", $employeeId)
            ->groupBy("ct.ClaimName")
            ->having("total_year", ">", 0)
            ->orderByDesc("total_year")
            ->get();

        // Filter out months with 0
        $filtered = $data->map(function ($row) {
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            foreach ($months as $month) {
                if ((float) $row->$month <= 0) {
                    unset($row->$month);
                }
            }
            return $row;
        });

        return response()->json([
            'success' => true,
            'data' => $filtered
        ]);
    }


    function getTop10TravelersSplitByWType($table, $startDate, $endDate)
    {
        return [];
    }
}


?>