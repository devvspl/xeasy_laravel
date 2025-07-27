<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function overview()
    {
        // Cache the dashboard data for 10 minutes
        $cacheKey = 'dashboard_overview';
        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            // Query 1: Combined claim status counts and total
            $claimStatusCounts = DB::table('y7_expenseclaims')
                ->selectRaw("IFNULL(LOWER(ClaimStatus), 'total') as ClaimStatus, COUNT(*) as TotalCount")
                ->groupByRaw('ClaimStatus WITH ROLLUP')
                ->get();

            // Query 2: Total financed amount by claim type
            $claimTypeTotals = DB::table('y7_expenseclaims')
                ->join('claimtype', 'claimtype.ClaimId', '=', 'y7_expenseclaims.ClaimId')
                ->select('claimtype.ClaimName', DB::raw('SUM(y7_expenseclaims.FinancedTAmt) as TotalFinancedAmount'))
                ->groupBy('claimtype.ClaimId', 'claimtype.ClaimName')
                ->get();

            // Query 3: Total financed amount by claim month
            $monthlyTotals = DB::table('y7_expenseclaims')
                ->select('ClaimMonth', DB::raw('SUM(FinancedTAmt) as TotalFinancedAmount'))
                ->where('ClaimMonth', '!=', 0)
                ->groupBy('ClaimMonth')
                ->get();

            // Query 4: Total financed amount by department for Claim Years 6 and 7
            $departmentTotals = DB::table(DB::raw('(SELECT ExpId, CrBy, ClaimYearId, FinancedTAmt FROM y6_expenseclaims UNION ALL SELECT ExpId, CrBy, ClaimYearId, FinancedTAmt FROM y7_expenseclaims) as e'))
                ->join('hrims.hrm_employee_general as gen', 'gen.EmployeeID', '=', 'e.CrBy')
                ->join('hrims.core_departments as dep', 'gen.DepartmentId', '=', 'dep.id')
                ->select(
                    'dep.department_name',
                    DB::raw('SUM(CASE WHEN e.ClaimYearId = 7 THEN e.FinancedTAmt ELSE 0 END) as TotalFinancedTAmt_Y7'),
                    DB::raw('SUM(CASE WHEN e.ClaimYearId = 6 THEN e.FinancedTAmt ELSE 0 END) as TotalFinancedTAmt_Y6')
                )
                ->whereNotNull('dep.department_name')
                ->groupBy('dep.department_name')
                ->orderBy('dep.department_name')
                ->get();

            // Query 5: Year-over-year expense comparison
            $yearlyComparison = DB::table(DB::raw('(SELECT ClaimYearId, FinancedTAmt FROM y6_expenseclaims UNION ALL SELECT ClaimYearId, FinancedTAmt FROM y7_expenseclaims) as e'))
                ->selectRaw('
                    SUM(CASE WHEN ClaimYearId = 7 THEN FinancedTAmt ELSE 0 END) as CY_Expense,
                    SUM(CASE WHEN ClaimYearId = 6 THEN FinancedTAmt ELSE 0 END) as PY_Expense,
                    SUM(CASE WHEN ClaimYearId = 7 THEN FinancedTAmt ELSE 0 END) - 
                    SUM(CASE WHEN ClaimYearId = 6 THEN FinancedTAmt ELSE 0 END) as Variance,
                    (SUM(CASE WHEN ClaimYearId = 7 THEN FinancedTAmt ELSE 0 END) - 
                    SUM(CASE WHEN ClaimYearId = 6 THEN FinancedTAmt ELSE 0 END)) / 
                    NULLIF(SUM(CASE WHEN ClaimYearId = 6 THEN FinancedTAmt ELSE 0 END), 0) * 100 as Variance_Percentage
                ')
                ->whereIn('ClaimYearId', [6, 7])
                ->first();

            // Map ClaimStatus to dashboard card labels
            $statusMap = [
                'draft' => 'Draft',
                'deactivated' => 'Deactivated',
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

            // Handle empty data
            $claimTypeTotals = $claimTypeTotals->isEmpty() ? collect([['ClaimName' => 'No Data', 'TotalFinancedAmount' => 0]]) : $claimTypeTotals;
            $departmentTotals = $departmentTotals->isEmpty() ? collect([['department_name' => 'No Data', 'TotalFinancedTAmt_Y7' => 0, 'TotalFinancedTAmt_Y6' => 0]]) : $departmentTotals;

            return compact('cardData', 'claimTypeTotals', 'monthlyTotals', 'departmentTotals', 'yearlyComparison');
        });

        return view('admin.overview', $data);
    }

    public function filter(Request $request)
    {
        $month = $request->input('month');
        $claimType = $request->input('claim_type');
        $department = $request->input('department');
        $claimStatus = $request->input('claim_status');

        // Query 1: Filtered claim status counts
        $query = DB::table('y7_expenseclaims')
            ->selectRaw("IFNULL(LOWER(ClaimStatus), 'total') as ClaimStatus, COUNT(*) as TotalCount")
            ->when($month, function ($query, $month) {
                return $query->where('ClaimMonth', $month);
            })
            ->when($claimType, function ($query, $claimType) {
                return $query->join('claimtype', 'claimtype.ClaimId', '=', 'y7_expenseclaims.ClaimId')
                    ->where('claimtype.ClaimName', $claimType);
            })
            ->when($claimStatus, function ($query, $claimStatus) {
                return $query->where('ClaimStatus', $claimStatus);
            });

        $claimStatusCounts = $query->groupByRaw('ClaimStatus WITH ROLLUP')->get();

        // Query 2: Filtered claim type totals
        $claimTypeQuery = DB::table('y7_expenseclaims')
            ->join('claimtype', 'claimtype.ClaimId', '=', 'y7_expenseclaims.ClaimId')
            ->select('claimtype.ClaimName', DB::raw('SUM(y7_expenseclaims.FinancedTAmt) as TotalFinancedAmount'))
            ->when($month, function ($query, $month) {
                return $query->where('ClaimMonth', $month);
            })
            ->when($claimType, function ($query, $claimType) {
                return $query->where('claimtype.ClaimName', $claimType);
            })
            ->when($claimStatus, function ($query, $claimStatus) {
                return $query->where('ClaimStatus', $claimStatus);
            })
            ->groupBy('claimtype.ClaimId', 'claimtype.ClaimName');

        $claimTypeTotals = $claimTypeQuery->get();

        // Query 3: Filtered department totals
        $departmentQuery = DB::table(DB::raw('(SELECT ExpId, CrBy, ClaimYearId, FinancedTAmt FROM y6_expenseclaims UNION ALL SELECT ExpId, CrBy, ClaimYearId, FinancedTAmt FROM y7_expenseclaims) as e'))
            ->join('hrims.hrm_employee_general as gen', 'gen.EmployeeID', '=', 'e.CrBy')
            ->join('hrims.core_departments as dep', 'gen.DepartmentId', '=', 'dep.id')
            ->select(
                'dep.department_name',
                DB::raw('SUM(CASE WHEN e.ClaimYearId = 7 THEN e.FinancedTAmt ELSE 0 END) as TotalFinancedTAmt_Y7'),
                DB::raw('SUM(CASE WHEN e.ClaimYearId = 6 THEN e.FinancedTAmt ELSE 0 END) as TotalFinancedTAmt_Y6')
            )
            ->whereNotNull('dep.department_name')
            ->when($department, function ($query, $department) {
                return $query->where('dep.department_name', $department);
            })
            ->when($month, function ($query, $month) {
                return $query->where('ClaimMonth', $month);
            })
            ->when($claimType, function ($query, $claimType) {
                return $query->join('claimtype', 'claimtype.ClaimId', '=', 'e.ClaimId')
                    ->where('claimtype.ClaimName', $claimType);
            })
            ->when($claimStatus, function ($query, $claimStatus) {
                return $query->where('ClaimStatus', $claimStatus);
            })
            ->groupBy('dep.department_name')
            ->orderBy('dep.department_name');

        $departmentTotals = $departmentQuery->get();

        // Map ClaimStatus to cardData
        $statusMap = [
            'draft' => 'Draft',
            'deactivated' => 'Deactivated',
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
                $cardData[] = [
                    'originalValue' => $status->TotalCount,
                    'value' => $status->TotalCount,
                    'label' => $statusMap[$key]
                ];
            }
        }

        // Handle empty data
        $claimTypeTotals = $claimTypeTotals->isEmpty() ? collect([['ClaimName' => 'No Data', 'TotalFinancedAmount' => 0]]) : $claimTypeTotals;
        $departmentTotals = $departmentTotals->isEmpty() ? collect([['department_name' => 'No Data', 'TotalFinancedTAmt_Y7' => 0, 'TotalFinancedTAmt_Y6' => 0]]) : $departmentTotals;

        return response()->json(compact('cardData', 'claimTypeTotals', 'departmentTotals'));
    }
}