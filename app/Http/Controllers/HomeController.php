<?php

namespace App\Http\Controllers;

use App\Exports\dashboard\DepartmentExpenseComparisonExport;
use App\Exports\dashboard\ExpenseMonthWiseReportExport;
use App\Exports\dashboard\ExpenseDepartmentWiseReportExport;
use App\Exports\dashboard\ExpenseClaimTypeWiseReportExport;
use App\Models\ExpenseClaim;
use App\Models\CoreDepartments;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

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
            $params = $this->prepareDateAndTableParams($request);
            if ($params['yearId'] <= 0) {
                return $this->errorResponse('Invalid year ID in session.', $this->defaultDashboardData($params['yearId']));
            }

            $data = $this->fetchDashboardData($params);
            $cardData = $this->formatCardData($data['claimStatusCounts']);
            $data['claimTypeTotals'] = $data['claimTypeTotals']->isEmpty()
                ? collect([['ClaimName' => 'No Data', 'ClaimCode' => '', 'FilledTotal' => 0, 'VerifiedTotal' => 0, 'ApprovedTotal' => 0, 'FinancedTotal' => 0]])
                : $data['claimTypeTotals'];
            $data['departmentTotals'] = $data['departmentTotals']->isEmpty()
                ? collect([['department_name' => 'No Data', "TotalFinancedTAmt_Y{$params['yearId']}" => 0, "TotalFinancedTAmt_Y{$params['previousYearId']}" => 0, 'VariationPercentage' => 0]])
                : $data['departmentTotals'];

            return response()->json([
                'cardData' => $cardData,
                'claimTypeTotals' => $data['claimTypeTotals'],
                'monthlyTotals' => $data['monthlyStatusTotals'],
                'totalAllMonths' => $data['totalAllMonths'],
                'departmentTotals' => $data['departmentTotals'],
                'top10TravelersSplitByWType' => $data['top10TravelersSplitByWType'],
                'yearlyComparison' => $data['yearlyComparison'],
                'topEmployees' => $data['topEmployees'],
                'yearId' => $params['yearId'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getDashboardData', [
                'bill_date_from' => $request->input('bill_date_from') ?? 'N/A',
                'bill_date_to' => $request->input('bill_date_to') ?? 'N/A',
                'year_id' => session('year_id') ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Server error occurred'], 500);
        }
    }

    public function analytics(Request $request)
    {
        $page = $request->route('page');
        $departments = CoreDepartments::where('is_active', 1)->get(['id', 'department_name']);
        $view = 'reports.' . $page;

        if (!view()->exists($view)) {
            abort(404, "Page not found");
        }

        return view($view, compact('page', 'departments'));
    }

    public function getSubDepartments(Request $request)
    {
        $request->validate([
            'bill_date_from' => 'required|date_format:Y-m-d',
            'bill_date_to' => 'required|date_format:Y-m-d|after_or_equal:bill_date_from',
        ]);

        $params = $this->prepareDateAndTableParams($request);
        $departmentId = $request->input('department_id');
        $departmentTotals = $this->getSubDepartmentTotals(
            $params['table'],
            $params['previousYearTable'],
            $params['yearId'],
            $params['previousYearId'],
            $params['startDate'],
            $params['endDate'],
            $params['previousYearStartDate'],
            $params['previousYearEndDate'],
            $departmentId
        );

        return $this->jsonSuccess($departmentTotals, 'Sub Department expense analytics loaded successfully.');
    }

    public function analyticsDashboardData(Request $request)
    {
        $request->validate([
            'bill_date_from' => 'required|date_format:Y-m-d',
            'bill_date_to' => 'required|date_format:Y-m-d|after_or_equal:bill_date_from',
            'filter_type' => 'nullable|in:all,increased,decreased,critical',
            'sort_by' => 'nullable|in:variation,current,previous',
        ]);

        $params = $this->prepareDateAndTableParams($request);
        $departmentId = $request->input('department');

        $departmentTotals = $this->getDepartmentTotals(
            $params['table'],
            $params['previousYearTable'],
            $params['yearId'],
            $params['previousYearId'],
            $params['startDate'],
            $params['endDate'],
            $params['previousYearStartDate'],
            $params['previousYearEndDate'],
            $departmentId
        );

        $topEmployees = $this->getTopEmployees($params['table'], $params['startDate'], $params['endDate'], 10, null, $departmentId);
        $topEmployeesSameDay = $this->getTopEmployees($params['table'], $params['startDate'], $params['endDate'], 10, true, $departmentId);
        $topEmployeesRevert = $this->getTopEmployees($params['table'], $params['startDate'], $params['endDate'], 10, false, $departmentId);
        $departmentMonthlyTotals = $this->getDepartmentMonthlyTotals($params['table'], $params['startDate'], $params['endDate'], $departmentId);
        $departmentTotalsClaimTypeWise = $this->getDepartmentTotalsClaimTypeWise(
            $params['table'],
            $params['previousYearTable'],
            $params['yearId'],
            $params['previousYearId'],
            $params['startDate'],
            $params['endDate'],
            $params['previousYearStartDate'],
            $params['previousYearEndDate'],
            $departmentId
        );

        $data = $departmentTotals
            ->map(function ($item) use ($params) {
                return [
                    'department' => $item->department_name,
                    'department_id' => $item->department_id,
                    'code' => $item->department_code,
                    'previousYear' => (float) $item->{"TotalFinancedTAmt_Y{$params['previousYearId']}"},
                    'currentYear' => (float) $item->{"TotalFinancedTAmt_Y{$params['yearId']}"},
                    'variation' => (float) $item->VariationPercentage,
                ];
            })
            ->toArray();

        $formatted = $this->formatDepartmentClaimTypeTotals($departmentTotalsClaimTypeWise, $params['yearId'], $params['previousYearId']);
        $departmentTotalsClaimTypeWise = [
            'departments' => array_values($formatted['departments']),
            'claimTypeTotals' => $formatted['claimTypeTotals'],
            'grandTotals' => [
                ["TotalFinancedTAmt_Y{$params['yearId']}" => $formatted['grand_totals']["TotalFinancedTAmt_Y{$params['yearId']}"]],
                ["TotalFinancedTAmt_Y{$params['previousYearId']}" => $formatted['grand_totals']["TotalFinancedTAmt_Y{$params['previousYearId']}"]],
                ['VariationPercentage' => $formatted['grand_totals']['VariationPercentage']],
            ],
        ];

        $data = [
            'departments' => $data,
            'topEmployees' => $topEmployees,
            'topEmployeesSameDay' => $topEmployeesSameDay,
            'topEmployeesRevert' => $topEmployeesRevert,
            'departmentMonthlyTotals' => $departmentMonthlyTotals,
            'departmentTotalsClaimTypeWise' => $departmentTotalsClaimTypeWise,
        ];

        return $this->jsonSuccess($data, 'Department expense analytics loaded successfully.');
    }

    public function exportReports(Request $request)
    {
        $request->validate([
            'bill_date_from' => 'required|date_format:Y-m-d',
            'bill_date_to' => 'required|date_format:Y-m-d|after_or_equal:bill_date_from',
            'reports' => 'required|array',
            'reports.*' => 'in:department_expense_comparison,expense_month_wise,expense_department_wise,expense_claim_type_wise',
        ]);

        try {
            $params = $this->prepareDateAndTableParams($request);
            $reports = $request->input('reports');
            $departmentId = $request->input('department');

            // Use a unique timestamp for the temporary directory
            $timestamp = time();
            $tempDir = storage_path("app/temp/reports_$timestamp");
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Define the ZIP file path in the public directory
            $zipFileName = public_path("reports_{$params['startDate']}_to_{$params['endDate']}.zip");
            $zip = new \ZipArchive();
            if ($zip->open($zipFileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                Log::error('Failed to create ZIP file', ['path' => $zipFileName]);
                return response()->json(['error' => 'Could not create ZIP file'], 500);
            }

            $filesAdded = false; // Track if any files are added to the ZIP
            foreach ($reports as $report) {
                $exportData = $this->prepareExportData($report, $params, $departmentId);
                if ($exportData['export'] && $exportData['fileName']) {
                    $filePath = storage_path("app/temp/reports_$timestamp/{$exportData['fileName']}");

                    // Store Excel file in the temporary directory
                    Excel::store($exportData['export'], "temp/reports_$timestamp/{$exportData['fileName']}", 'local');

                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, $exportData['fileName']);
                        $filesAdded = true;
                    } else {
                        Log::warning('Excel file not found for report', [
                            'report' => $report,
                            'filePath' => $filePath,
                        ]);
                    }
                } else {
                    Log::warning('No export data generated for report', ['report' => $report]);
                }
            }

            $zip->close();

            // Check if any files were added to the ZIP
            if (!$filesAdded) {
                Log::error('No files added to ZIP', ['zipFile' => $zipFileName, 'reports' => $reports]);
                foreach (glob("$tempDir/*") as $file) {
                    unlink($file);
                }
                rmdir($tempDir);
                return response()->json(['error' => 'No valid reports generated'], 400);
            }

            // Verify the ZIP file exists before attempting to serve it
            if (!file_exists($zipFileName)) {
                Log::error('ZIP file does not exist after creation', ['path' => $zipFileName]);
                foreach (glob("$tempDir/*") as $file) {
                    unlink($file);
                }
                rmdir($tempDir);
                return response()->json(['error' => 'Failed to generate ZIP file'], 500);
            }

            // Cleanup temporary Excel files
            foreach (glob("$tempDir/*") as $file) {
                unlink($file);
            }
            rmdir($tempDir);

            // Serve the ZIP file for download
            return response()->download($zipFileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error in exportReports', [
                'bill_date_from' => $request->input('bill_date_from') ?? 'N/A',
                'bill_date_to' => $request->input('bill_date_to') ?? 'N/A',
                'reports' => $request->input('reports') ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Server error occurred'], 500);
        }
    }

    public function exportExpenseMonthWise(Request $request)
    {
        $request->validate([
            'bill_date_from' => 'required|date_format:Y-m-d',
            'bill_date_to' => 'required|date_format:Y-m-d|after_or_equal:bill_date_from',
        ]);

        $params = $this->prepareDateAndTableParams($request);
        $monthlyTotals = $this->getRawMonthlyTotals($params['table'], $params['startDate'], $params['endDate'])->collect();
        $totalAllMonths = [
            'filled' => $monthlyTotals->sum('FilledTotal'),
            'verified' => $monthlyTotals->sum('VerifiedTotal'),
            'approved' => $monthlyTotals->sum('ApprovedTotal'),
            'financed' => $monthlyTotals->sum('FinancedTotal'),
        ];

        return Excel::download(new ExpenseMonthWiseReportExport($monthlyTotals, $totalAllMonths), "Expense_Month_Wise_{$params['startDate']}_to_{$params['endDate']}.xlsx");
    }

    public function exportExpenseDepartmentWise(Request $request)
    {
        $request->validate([
            'bill_date_from' => 'required|date_format:Y-m-d',
            'bill_date_to' => 'required|date_format:Y-m-d|after_or_equal:bill_date_from',
        ]);

        $params = $this->prepareDateAndTableParams($request);
        $departmentTotals = $this->getDepartmentTotals(
            $params['table'],
            $params['previousYearTable'],
            $params['yearId'],
            $params['previousYearId'],
            $params['startDate'],
            $params['endDate'],
            $params['previousYearStartDate'],
            $params['previousYearEndDate']
        )->collect();

        if ($departmentTotals->isEmpty()) {
            $departmentTotals = collect([
                [
                    'department_name' => 'No Data',
                    "TotalFinancedTAmt_Y{$params['yearId']}" => 0,
                    "TotalFinancedTAmt_Y{$params['previousYearId']}" => 0,
                    'VariationPercentage' => 0,
                ],
            ]);
        }

        $totalAllDepartments = [
            "TotalFinancedTAmt_Y{$params['yearId']}" => $departmentTotals->sum("TotalFinancedTAmt_Y{$params['yearId']}"),
            "TotalFinancedTAmt_Y{$params['previousYearId']}" => $departmentTotals->sum("TotalFinancedTAmt_Y{$params['previousYearId']}"),
        ];
        $totalAllDepartments['VariationPercentage'] =
            $totalAllDepartments["TotalFinancedTAmt_Y{$params['previousYearId']}"] > 0
            ? round(
                (($totalAllDepartments["TotalFinancedTAmt_Y{$params['yearId']}"] - $totalAllDepartments["TotalFinancedTAmt_Y{$params['previousYearId']}"]) / $totalAllDepartments["TotalFinancedTAmt_Y{$params['previousYearId']}"]) * 100,
                2
            )
            : ($totalAllDepartments["TotalFinancedTAmt_Y{$params['yearId']}"] > 0
                ? 100
                : null);

        return Excel::download(new ExpenseDepartmentWiseReportExport($departmentTotals, $params['yearId'], $totalAllDepartments), "Expense_Department_Wise_{$params['startDate']}_to_{$params['endDate']}.xlsx");
    }

    public function exportExpenseClaimTypeWise(Request $request)
    {
        $request->validate([
            'bill_date_from' => 'required|date_format:Y-m-d',
            'bill_date_to' => 'required|date_format:Y-m-d|after_or_equal:bill_date_from',
        ]);

        $params = $this->prepareDateAndTableParams($request);
        $claimTypeTotals = $this->getClaimTypeTotals($params['table'], $params['startDate'], $params['endDate']);
        $claimTypeTotals = $claimTypeTotals->isEmpty() ? collect([['ClaimName' => 'No Data', 'ClaimCode' => '', 'FilledTotal' => 0, 'VerifiedTotal' => 0, 'ApprovedTotal' => 0, 'FinancedTotal' => 0]]) : $claimTypeTotals;

        return Excel::download(new ExpenseClaimTypeWiseReportExport($claimTypeTotals), "Claim_Type_Wise_Report_{$params['startDate']}_to_{$params['endDate']}.xlsx");
    }

    public function getEmployeeTrend(Request $request)
    {
        $request->validate([
            'bill_date_from' => 'required|date_format:Y-m-d',
            'bill_date_to' => 'required|date_format:Y-m-d|after_or_equal:bill_date_from',
            'employee_id' => 'required',
        ]);

        $params = $this->prepareDateAndTableParams($request);
        $employeeId = $request->input('employee_id');

        $data = DB::table("{$params['table']} as e")
            ->join("claimtype as ct", "ct.ClaimId", "=", "e.ClaimId")
            ->select(
                "ct.ClaimName",
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 1 THEN e.FinancedTAmt ELSE 0 END) AS Jan"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 2 THEN e.FinancedTAmt ELSE 0 END) AS Feb"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 3 THEN e.FinancedTAmt ELSE 0 END) AS Mar"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 4 THEN e.FinancedTAmt ELSE 0 END) AS Apr"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 5 THEN e.FinancedTAmt ELSE 0 END) AS May"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 6 THEN e.FinancedTAmt ELSE 0 END) AS Jun"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 7 THEN e.FinancedTAmt ELSE 0 END) AS Jul"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 8 THEN e.FinancedTAmt ELSE 0 END) AS Aug"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 9 THEN e.FinancedTAmt ELSE 0 END) AS Sep"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 10 THEN e.FinancedTAmt ELSE 0 END) AS Oct"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 11 THEN e.FinancedTAmt ELSE 0 END) AS Nov"),
                DB::raw("SUM(CASE WHEN e.ClaimMonth = 12 THEN e.FinancedTAmt ELSE 0 END) AS `Dec`"),
                DB::raw("SUM(e.FinancedTAmt) AS total_year")
            )
            ->whereBetween("e.BillDate", [$params['startDate'], $params['endDate']])
            ->whereNotNull("e.BillDate")
            ->where("e.BillDate", "!=", "0000-00-00")
            ->whereNotIn("e.ClaimStatus", ["Draft", "Submitted", "Deactivate"])
            ->where("e.CrBy", $employeeId)
            ->groupBy("ct.ClaimName")
            ->having("total_year", ">", 0)
            ->orderByDesc("total_year")
            ->get();

        $filtered = $data->map(function ($row) {
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            foreach ($months as $month) {
                if ((float) $row->$month <= 0) {
                    unset($row->$month);
                }
            }
            return $row;
        });

        return response()->json(['success' => true, 'data' => $filtered]);
    }

    private function prepareDateAndTableParams(Request $request): array
    {
        $startDate = Carbon::parse($request->input('bill_date_from'))
            ->addDay()
            ->toDateString();
        $endDate = Carbon::parse($request->input('bill_date_to'))
            ->addDay()
            ->toDateString();
        $yearId = (int) session('year_id', Carbon::parse($endDate)->year);
        $previousYearId = $yearId - 1;
        $table = ExpenseClaim::tableName();
        $previousYearTable = ExpenseClaim::tableName($previousYearId);

        if (!DB::getSchemaBuilder()->hasTable($previousYearTable)) {
            $previousYearTable = $table;
        }

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'yearId' => $yearId,
            'previousYearId' => $previousYearId,
            'table' => $table,
            'previousYearTable' => $previousYearTable,
            'previousYearStartDate' => Carbon::parse($startDate)
                ->subYear()
                ->toDateString(),
            'previousYearEndDate' => Carbon::parse($endDate)
                ->subYear()
                ->toDateString(),
        ];
    }

    private function defaultDashboardData(int $yearId): array
    {
        $previousYearId = $yearId - 1;
        return [
            'cardData' => [],
            'claimTypeTotals' => collect([['ClaimName' => 'No Data', 'TotalFinancedAmount' => 0]]),
            'monthlyTotals' => collect([]),
            'departmentTotals' => collect([
                [
                    'department_name' => 'No Data',
                    "TotalFinancedTAmt_Y{$yearId}" => 0,
                    "TotalFinancedTAmt_Y{$previousYearId}" => 0,
                ],
            ]),
            'top10TravelersSplitByWType' => [],
            'yearlyComparison' => null,
            'totalAllMonths' => ['filled' => 0, 'verified' => 0, 'approved' => 0, 'financed' => 0],
        ];
    }

    private function fetchDashboardData(array $params): array
    {
        return [
            'claimStatusCounts' => $this->getClaimStatusCounts($params['table'], $params['startDate'], $params['endDate']),
            'claimTypeTotals' => $this->getClaimTypeTotals($params['table'], $params['startDate'], $params['endDate']),
            'monthlyStatusTotals' => $this->getMonthlyStatusTotals($params['table'], $params['startDate'], $params['endDate']),
            'monthlyTotals' => $this->getRawMonthlyTotals($params['table'], $params['startDate'], $params['endDate']),
            'top10TravelersSplitByWType' => $this->getTop10TravelersSplitByWType($params['table'], $params['startDate'], $params['endDate']),
            'departmentTotals' => $this->getDepartmentTotals(
                $params['table'],
                $params['previousYearTable'],
                $params['yearId'],
                $params['previousYearId'],
                $params['startDate'],
                $params['endDate'],
                $params['previousYearStartDate'],
                $params['previousYearEndDate']
            ),
            'topEmployees' => $this->getTopEmployees($params['table'], $params['startDate'], $params['endDate']),
            'yearlyComparison' => $this->getYearlyComparison(
                $params['table'],
                $params['previousYearTable'],
                $params['yearId'],
                $params['previousYearId'],
                $params['startDate'],
                $params['endDate'],
                $params['previousYearStartDate'],
                $params['previousYearEndDate']
            ),
            'totalAllMonths' => [
                'filled' => $this->getRawMonthlyTotals($params['table'], $params['startDate'], $params['endDate'])->sum('FilledTotal'),
                'verified' => $this->getRawMonthlyTotals($params['table'], $params['startDate'], $params['endDate'])->sum('VerifiedTotal'),
                'approved' => $this->getRawMonthlyTotals($params['table'], $params['startDate'], $params['endDate'])->sum('ApprovedTotal'),
                'financed' => $this->getRawMonthlyTotals($params['table'], $params['startDate'], $params['endDate'])->sum('FinancedTotal'),
            ],
        ];
    }

    private function formatCardData(Collection $claimStatusCounts): array
    {
        $statusMap = [
            'draft' => 'Draft',
            'deactivate' => 'Deactivate',
            'submitted' => 'Submitted',
            'filled' => 'Filled',
            'verified' => 'Verified',
            'approved' => 'Approved',
            'financed' => 'Financed',
            'total' => 'Total Expense',
        ];

        $cardData = [];
        foreach ($claimStatusCounts as $status) {
            $key = strtolower($status->ClaimStatus);
            if (isset($statusMap[$key])) {
                $cardData[$statusMap[$key]] = $status->TotalCount;
            }
        }
        return $cardData;
    }

    private function formatDepartmentClaimTypeTotals(Collection $departmentTotalsClaimTypeWise, int $yearId, int $previousYearId): array
    {
        $formatted = [
            'departments' => [],
            'claimTypeTotals' => [],
            'grand_totals' => [
                "TotalFinancedTAmt_Y{$yearId}" => 0,
                "TotalFinancedTAmt_Y{$previousYearId}" => 0,
                'VariationPercentage' => null,
            ],
        ];
        $claimTypeMap = [];

        foreach ($departmentTotalsClaimTypeWise as $row) {
            $row = (array) $row;
            $deptName = $row['department_name'] ?? 'Unknown';
            $cy = (float) ($row["TotalFinancedTAmt_Y{$yearId}"] ?? 0);
            $py = (float) ($row["TotalFinancedTAmt_Y{$previousYearId}"] ?? 0);

            if (!isset($formatted['departments'][$deptName])) {
                $formatted['departments'][$deptName] = [
                    'department_name' => $deptName,
                    'claims' => [],
                    'totals' => [
                        "TotalFinancedTAmt_Y{$yearId}" => 0,
                        "TotalFinancedTAmt_Y{$previousYearId}" => 0,
                        'VariationPercentage' => null,
                    ],
                ];
            }

            $formatted['departments'][$deptName]['claims'][] = [
                'ClaimName' => $row['ClaimName'],
                'ClaimCode' => $row['ClaimCode'],
                "TotalFinancedTAmt_Y{$yearId}" => $cy,
                "TotalFinancedTAmt_Y{$previousYearId}" => $py,
                'VariationPercentage' => $row['VariationPercentage'] !== null ? (float) $row['VariationPercentage'] : null,
            ];

            $formatted['departments'][$deptName]['totals']["TotalFinancedTAmt_Y{$yearId}"] += $cy;
            $formatted['departments'][$deptName]['totals']["TotalFinancedTAmt_Y{$previousYearId}"] += $py;

            $claimCode = $row['ClaimCode'];
            if (!isset($claimTypeMap[$claimCode])) {
                $claimTypeMap[$claimCode] = [
                    'ClaimName' => $row['ClaimName'],
                    'ClaimCode' => $claimCode,
                    "TotalFinancedTAmt_Y{$yearId}" => 0,
                    "TotalFinancedTAmt_Y{$previousYearId}" => 0,
                    'VariationPercentage' => null,
                ];
            }
            $claimTypeMap[$claimCode]["TotalFinancedTAmt_Y{$yearId}"] += $cy;
            $claimTypeMap[$claimCode]["TotalFinancedTAmt_Y{$previousYearId}"] += $py;
            $formatted['grand_totals']["TotalFinancedTAmt_Y{$yearId}"] += $cy;
            $formatted['grand_totals']["TotalFinancedTAmt_Y{$previousYearId}"] += $py;
        }

        foreach ($formatted['departments'] as &$dept) {
            $cy = $dept['totals']["TotalFinancedTAmt_Y{$yearId}"];
            $py = $dept['totals']["TotalFinancedTAmt_Y{$previousYearId}"];
            $dept['totals']['VariationPercentage'] = $py > 0 ? round((($cy - $py) / $py) * 100, 2) : ($cy > 0 ? 100 : null);
        }

        foreach ($claimTypeMap as &$claim) {
            $cy = $claim["TotalFinancedTAmt_Y{$yearId}"];
            $py = $claim["TotalFinancedTAmt_Y{$previousYearId}"];
            $claim['VariationPercentage'] = $py > 0 ? round((($cy - $py) / $py) * 100, 2) : ($cy > 0 ? 100 : null);
        }

        $formatted['claimTypeTotals'] = array_values($claimTypeMap);
        $cyGrand = $formatted['grand_totals']["TotalFinancedTAmt_Y{$yearId}"];
        $pyGrand = $formatted['grand_totals']["TotalFinancedTAmt_Y{$previousYearId}"];
        $formatted['grand_totals']['VariationPercentage'] = $pyGrand > 0 ? round((($cyGrand - $pyGrand) / $pyGrand) * 100, 2) : ($cyGrand > 0 ? 100 : null);

        return $formatted;
    }

    private function prepareExportData(string $report, array $params, ?string $departmentId): array
    {
        $export = null;
        $fileName = null;

        switch ($report) {
            case 'department_expense_comparison':
                $departmentTotals = $this->getDepartmentTotals(
                    $params['table'],
                    $params['previousYearTable'],
                    $params['yearId'],
                    $params['previousYearId'],
                    $params['startDate'],
                    $params['endDate'],
                    $params['previousYearStartDate'],
                    $params['previousYearEndDate'],
                    $departmentId
                );

                $departmentTotalsClaimTypeWise = $this->getDepartmentTotalsClaimTypeWise(
                    $params['table'],
                    $params['previousYearTable'],
                    $params['yearId'],
                    $params['previousYearId'],
                    $params['startDate'],
                    $params['endDate'],
                    $params['previousYearStartDate'],
                    $params['previousYearEndDate'],
                    $departmentId
                );

                $formatted = $this->formatDepartmentClaimTypeTotals($departmentTotalsClaimTypeWise, $params['yearId'], $params['previousYearId']);
                $export = new DepartmentExpenseComparisonExport($departmentTotals, $params['yearId'], $params['previousYearId']);
                $fileName = "Department_Expense_Comparison_{$params['yearId']}_vs_{$params['previousYearId']}_{$params['startDate']}_to_{$params['endDate']}.xlsx";
                break;

            case 'expense_month_wise':
                $monthlyTotals = $this->getRawMonthlyTotals($params['table'], $params['startDate'], $params['endDate'])->collect();
                $totalAllMonths = [
                    'filled' => $monthlyTotals->sum('FilledTotal'),
                    'verified' => $monthlyTotals->sum('VerifiedTotal'),
                    'approved' => $monthlyTotals->sum('ApprovedTotal'),
                    'financed' => $monthlyTotals->sum('FinancedTotal'),
                ];
                $export = new ExpenseMonthWiseReportExport($monthlyTotals, $totalAllMonths);
                $fileName = "Expense_Month_Wise_{$params['startDate']}_to_{$params['endDate']}.xlsx";
                break;

            case 'expense_department_wise':
                $departmentTotals = $this->getDepartmentTotals(
                    $params['table'],
                    $params['previousYearTable'],
                    $params['yearId'],
                    $params['previousYearId'],
                    $params['startDate'],
                    $params['endDate'],
                    $params['previousYearStartDate'],
                    $params['previousYearEndDate'],
                    $departmentId
                )->collect();

                if ($departmentTotals->isEmpty()) {
                    $departmentTotals = collect([
                        [
                            'department_name' => 'No Data',
                            "TotalFinancedTAmt_Y{$params['yearId']}" => 0,
                            "TotalFinancedTAmt_Y{$params['previousYearId']}" => 0,
                            'VariationPercentage' => 0,
                        ],
                    ]);
                }

                $totalAllDepartments = [
                    "TotalFinancedTAmt_Y{$params['yearId']}" => $departmentTotals->sum("TotalFinancedTAmt_Y{$params['yearId']}"),
                    "TotalFinancedTAmt_Y{$params['previousYearId']}" => $departmentTotals->sum("TotalFinancedTAmt_Y{$params['previousYearId']}"),
                ];
                $totalAllDepartments['VariationPercentage'] =
                    $totalAllDepartments["TotalFinancedTAmt_Y{$params['previousYearId']}"] > 0
                    ? round(
                        (($totalAllDepartments["TotalFinancedTAmt_Y{$params['yearId']}"] - $totalAllDepartments["TotalFinancedTAmt_Y{$params['previousYearId']}"]) /
                            $totalAllDepartments["TotalFinancedTAmt_Y{$params['previousYearId']}"]) *
                        100,
                        2
                    )
                    : ($totalAllDepartments["TotalFinancedTAmt_Y{$params['yearId']}"] > 0
                        ? 100
                        : null);

                $export = new ExpenseDepartmentWiseReportExport($departmentTotals, $params['yearId'], $totalAllDepartments);
                $fileName = "Expense_Department_Wise_{$params['startDate']}_to_{$params['endDate']}.xlsx";
                break;

            case 'expense_claim_type_wise':
                $claimTypeTotals = $this->getClaimTypeTotals($params['table'], $params['startDate'], $params['endDate']);
                $claimTypeTotals = $claimTypeTotals->isEmpty() ? collect([['ClaimName' => 'No Data', 'ClaimCode' => '', 'FilledTotal' => 0, 'VerifiedTotal' => 0, 'ApprovedTotal' => 0, 'FinancedTotal' => 0]]) : $claimTypeTotals;

                $export = new ExpenseClaimTypeWiseReportExport($claimTypeTotals);
                $fileName = "Claim_Type_Wise_Report_{$params['startDate']}_to_{$params['endDate']}.xlsx";
                break;
        }

        return ['export' => $export, 'fileName' => $fileName];
    }

    private function getClaimStatusCounts($tableName, $startDate, $endDate)
    {
        return DB::table($tableName)
            ->selectRaw("IFNULL(LOWER(ClaimStatus), 'total') as ClaimStatus, COUNT(*) as TotalCount")
            ->where('BillDate', '!=', '0000-00-00')
            ->whereNotNull('BillDate')
            ->whereBetween('BillDate', [$startDate, $endDate])
            ->groupByRaw('ClaimStatus WITH ROLLUP')
            ->get();
    }

    private function getClaimTypeTotals($tableName, $startDate, $endDate)
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

    private function getMonthlyStatusTotals($tableName, $startDate, $endDate)
    {
        return DB::table($tableName)
            ->selectRaw(
                "
                ELT(ClaimMonth, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December') as MonthName,
                SUM(CASE WHEN FilledBy > 0 AND FilledDate != '0000-00-00' THEN FilledTAmt ELSE 0 END) AS FilledTotal,
                SUM(CASE WHEN VerifyBy > 0 AND VerifyDate != '0000-00-00' THEN VerifyTAmt ELSE 0 END) AS VerifiedTotal,
                SUM(CASE WHEN ApprBy > 0 AND ApprDate != '0000-00-00' THEN ApprTAmt ELSE 0 END) AS ApprovedTotal,
                SUM(CASE WHEN FinancedBy > 0 AND FinancedDate != '0000-00-00' THEN FinancedTAmt ELSE 0 END) AS FinancedTotal,
                CASE WHEN ClaimMonth >= 4 THEN ClaimMonth ELSE ClaimMonth + 12 END AS FiscalOrder
            "
            )
            ->whereBetween('BillDate', [$startDate, $endDate])
            ->where('BillDate', '!=', '0000-00-00')
            ->whereNotNull('BillDate')
            ->whereBetween('ClaimMonth', [1, 12])
            ->whereNotIn('ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
            ->groupBy('ClaimMonth')
            ->orderBy('FiscalOrder')
            ->get();
    }

    private function getRawMonthlyTotals($tableName, $startDate, $endDate)
    {
        return DB::table($tableName)
            ->select(
                'ClaimMonth',
                DB::raw('SUM(CASE WHEN FilledBy > 0 AND FilledDate != "0000-00-00" THEN FilledTAmt ELSE 0 END) AS FilledTotal'),
                DB::raw('SUM(CASE WHEN VerifyBy > 0 AND VerifyDate != "0000-00-00" THEN VerifyTAmt ELSE 0 END) AS VerifiedTotal'),
                DB::raw('SUM(CASE WHEN ApprBy > 0 AND ApprDate != "0000-00-00" THEN ApprTAmt ELSE 0 END) AS ApprovedTotal'),
                DB::raw('SUM(CASE WHEN FinancedBy > 0 AND FinancedDate != "0000-00-00" THEN FinancedTAmt ELSE 0 END) AS FinancedTotal')
            )
            ->whereBetween('BillDate', [$startDate, $endDate])
            ->where('BillDate', '!=', '0000-00-00')
            ->whereNotNull('BillDate')
            ->where('ClaimMonth', '!=', 0)
            ->whereNotIn('ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
            ->groupBy('ClaimMonth')
            ->orderBy('ClaimMonth')
            ->get();
    }

    private function getRawTotals(string $tableName, $startDate, $endDate)
    {
        return DB::table($tableName)
            ->selectRaw(
                '
                SUM(CASE WHEN FilledBy > 0 AND FilledDate != "0000-00-00" THEN FilledTAmt ELSE 0 END) AS FilledTotal,
                SUM(CASE WHEN VerifyBy > 0 AND VerifyDate != "0000-00-00" THEN VerifyTAmt ELSE 0 END) AS VerifiedTotal,
                SUM(CASE WHEN ApprBy > 0 AND ApprDate != "0000-00-00" THEN ApprTAmt ELSE 0 END) AS ApprovedTotal,
                SUM(CASE WHEN FinancedBy > 0 AND FinancedDate != "0000-00-00" THEN FinancedTAmt ELSE 0 END) AS FinancedTotal
            '
            )
            ->whereBetween('BillDate', [$startDate, $endDate])
            ->where('BillDate', '!=', '0000-00-00')
            ->whereNotNull('BillDate')
            ->where('ClaimMonth', '!=', 0)
            ->whereNotIn('ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
            ->first();
    }

    private function getDepartmentTotals($tableName, $previousYearTable, $yearId, $previousYearId, $startDate, $endDate, $previousYearStartDate, $previousYearEndDate, $departmentId = null)
    {
        $query = DB::table(
            DB::raw("(
            SELECT ExpId, CrBy, ClaimYearId, FinancedTAmt, ClaimStatus, BillDate, FinancedBy 
            FROM {$previousYearTable}
            WHERE BillDate BETWEEN '{$previousYearStartDate}' AND '{$previousYearEndDate}'
            UNION ALL 
            SELECT ExpId, CrBy, ClaimYearId, FinancedTAmt, ClaimStatus, BillDate, FinancedBy 
            FROM {$tableName}
            WHERE BillDate BETWEEN '{$startDate}' AND '{$endDate}'
        ) as e")
        )
            ->leftJoin('hrims.hrm_employee_general as gen', 'gen.EmployeeID', '=', 'e.CrBy')
            ->leftJoin('hrims.core_departments as dep', 'gen.DepartmentId', '=', 'dep.id')
            ->whereNotIn('e.ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
            ->where('e.BillDate', '!=', '0000-00-00')
            ->whereNotNull('e.BillDate')
            ->where('e.FinancedBy', '!=', '0');

        if ($departmentId) {
            $query->whereIn('dep.id', (array) $departmentId);
        }

        return $query
            ->groupBy('dep.id', 'dep.department_name', 'dep.department_code')
            ->select(
                'dep.department_name',
                'dep.id as department_id',
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

    private function getSubDepartmentTotals($tableName, $previousYearTable, $yearId, $previousYearId, $startDate, $endDate, $previousYearStartDate, $previousYearEndDate, $departmentId = null)
    {
        $query = DB::table(
            DB::raw("(
            SELECT ExpId, CrBy, ClaimYearId, FinancedTAmt, ClaimStatus, BillDate, FinancedBy
            FROM {$previousYearTable}
            WHERE BillDate BETWEEN '{$previousYearStartDate}' AND '{$previousYearEndDate}'
            UNION ALL 
            SELECT ExpId, CrBy, ClaimYearId, FinancedTAmt, ClaimStatus, BillDate, FinancedBy
            FROM {$tableName}
            WHERE BillDate BETWEEN '{$startDate}' AND '{$endDate}'
        ) as e")
        )
            ->leftJoin('hrims.hrm_employee_general as gen', 'gen.EmployeeID', '=', 'e.CrBy')
            ->leftJoin('hrims.core_departments as dep', 'gen.DepartmentId', '=', 'dep.id')
            ->leftJoin('hrims.core_sub_department_master as sub', 'gen.SubDepartmentId', '=', 'sub.id')
            ->whereNotIn('e.ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
            ->where('e.BillDate', '!=', '0000-00-00')
            ->whereNotNull('e.BillDate')
            ->where('e.FinancedBy', '!=', '0');

        if ($departmentId) {
            $query->where('dep.id', $departmentId);
        }

        return $query
            ->groupBy('dep.id', 'dep.department_name', 'dep.department_code', 'sub.id', 'sub.sub_department_name', 'sub.sub_department_code')
            ->select(
                'dep.id as department_id',
                'dep.department_name',
                'dep.department_code',
                'sub.id as sub_department_id',
                'sub.sub_department_name',
                'sub.sub_department_code',
                DB::raw("SUM(CASE WHEN e.ClaimYearId = {$yearId} THEN e.FinancedTAmt ELSE 0 END) as TotalFinancedTAmt_Y{$yearId}"),
                DB::raw("SUM(CASE WHEN e.ClaimYearId = {$previousYearId} THEN e.FinancedTAmt ELSE 0 END) as TotalFinancedTAmt_Y{$previousYearId}"),
                DB::raw("
                    ROUND(
                        CASE 
                            WHEN SUM(CASE WHEN e.ClaimYearId = {$previousYearId} THEN e.FinancedTAmt ELSE 0 END) = 0 
                            THEN NULL
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
            ->orderBy('sub.sub_department_name')
            ->get();
    }

    private function getYearlyComparison($tableName, $previousYearTable, $yearId, $previousYearId, $startDate, $endDate, $previousYearStartDate, $previousYearEndDate)
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
            ->selectRaw(
                "
                SUM(CASE WHEN ClaimYearId = ? THEN FinancedTAmt ELSE 0 END) as CY_Expense,
                SUM(CASE WHEN ClaimYearId = ? THEN FinancedTAmt ELSE 0 END) as PY_Expense,
                SUM(CASE WHEN ClaimYearId = ? THEN FinancedTAmt ELSE 0 END) - SUM(CASE WHEN ClaimYearId = ? THEN FinancedTAmt ELSE 0 END) as Variance,
                (SUM(CASE WHEN ClaimYearId = ? THEN FinancedTAmt ELSE 0 END) - SUM(CASE WHEN ClaimYearId = ? THEN FinancedTAmt ELSE 0 END)) / NULLIF(SUM(CASE WHEN ClaimYearId = ? THEN FinancedTAmt ELSE 0 END), 0) * 100 as Variance_Percentage
            ",
                [$yearId, $previousYearId, $yearId, $previousYearId, $yearId, $previousYearId, $previousYearId]
            )
            ->whereIn('ClaimYearId', [$previousYearId, $yearId])
            ->where('BillDate', '!=', '0000-00-00')
            ->whereNotNull('BillDate')
            ->where('FinancedBy', '!=', '0')
            ->first();
    }

    private function getTopEmployees($tableName, $startDate, $endDate, $limit = 10, $sameDay = null, $departmentId = null)
    {
        $query = DB::table("{$tableName} as e")
            ->join('hrims.hrm_employee as emp', 'emp.EmployeeID', '=', 'e.CrBy')
            ->join('hrims.hrm_employee_general as gen', 'gen.EmployeeID', '=', 'e.CrBy')
            ->leftJoin('hrims.core_departments as dep', 'gen.DepartmentId', '=', 'dep.id')
            ->whereBetween('e.BillDate', [$startDate, $endDate])
            ->where('e.BillDate', '!=', '0000-00-00')
            ->whereNotNull('e.BillDate')
            ->where('e.FinancedBy', '!=', '0')
            ->whereNotIn('e.ClaimStatus', ['Draft', 'Submitted', 'Deactivate']);

        if ($sameDay === true) {
            $query->whereRaw('DATE(e.BillDate) = DATE(e.CrDate)');
        } elseif ($sameDay === false) {
            $query->whereRaw('DATE(e.BillDate) <> DATE(e.CrDate)');
        }

        if ($departmentId) {
            $query->whereIn('dep.id', (array) $departmentId);
        }

        return $query
            ->groupBy('e.CrBy', 'emp.Fname', 'emp.Sname', 'emp.Lname', 'emp.EmpCode', 'dep.department_name', 'dep.department_code')
            ->select(
                'e.CrBy',
                DB::raw("CONCAT(emp.Fname, ' ', emp.Sname, ' ', emp.Lname) AS employee_name"),
                'emp.EmpCode',
                'dep.department_name',
                'dep.department_code',
                DB::raw('SUM(e.FilledTAmt) as filled_total_amount'),
                DB::raw('SUM(e.FinancedTAmt) as payment_total_amount'),
                DB::raw('COUNT(e.ExpId) as claim_count'),
                DB::raw('MIN(e.BillDate) as first_bill_date'),
                DB::raw('MAX(e.BillDate) as last_bill_date')
            )
            ->orderByDesc('claim_count')
            ->limit($limit)
            ->get();
    }

    private function getTop10TravelersSplitByWType($table, $startDate, $endDate)
    {
        return [];
    }

    private function getDepartmentMonthlyTotals($tableName, $startDate, $endDate, $departmentId = null)
    {
        $query = DB::table("{$tableName} as e")
            ->join('hrims.hrm_employee_general as gen', 'gen.EmployeeID', '=', 'e.CrBy')
            ->leftJoin('hrims.core_departments as dep', 'gen.DepartmentId', '=', 'dep.id')
            ->whereBetween('e.BillDate', [$startDate, $endDate])
            ->where('e.BillDate', '!=', '0000-00-00')
            ->whereNotNull('e.BillDate')
            ->whereBetween('e.ClaimMonth', [1, 12])
            ->whereNotIn('e.ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
            ->select(
                'dep.department_name',
                DB::raw("ELT(e.ClaimMonth, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December') AS MonthName"),
                DB::raw("SUM(CASE WHEN e.FinancedBy > 0 AND e.FinancedDate != '0000-00-00' THEN e.FinancedTAmt ELSE 0 END) AS FinancedTotal"),
                DB::raw("CASE WHEN e.ClaimMonth >= 4 THEN e.ClaimMonth ELSE e.ClaimMonth + 12 END AS FiscalOrder")
            )
            ->groupBy('dep.department_name', 'e.ClaimMonth')
            ->orderBy('dep.department_name', 'asc')
            ->orderBy('FiscalOrder', 'asc');

        if ($departmentId) {
            $query->whereIn('dep.id', (array) $departmentId);
        }

        return $query->get();
    }

    private function getDepartmentTotalsClaimTypeWise($tableName, $previousYearTable, $yearId, $previousYearId, $startDate, $endDate, $previousYearStartDate, $previousYearEndDate, $departmentId = null)
    {
        $query = DB::table(
            DB::raw("(
            SELECT ExpId, CrBy, ClaimId, ClaimYearId, FinancedTAmt, ClaimStatus, BillDate, FinancedBy 
            FROM {$previousYearTable}
            WHERE BillDate BETWEEN '{$previousYearStartDate}' AND '{$previousYearEndDate}'
            UNION ALL 
            SELECT ExpId, CrBy, ClaimId, ClaimYearId, FinancedTAmt, ClaimStatus, BillDate, FinancedBy 
            FROM {$tableName}
            WHERE BillDate BETWEEN '{$startDate}' AND '{$endDate}'
        ) as e")
        )
            ->leftJoin('hrims.hrm_employee_general as gen', 'gen.EmployeeID', '=', 'e.CrBy')
            ->leftJoin('hrims.core_departments as dep', 'gen.DepartmentId', '=', 'dep.id')
            ->leftJoin('claimtype as ct', 'ct.ClaimId', '=', 'e.ClaimId')
            ->whereNotIn('e.ClaimStatus', ['Draft', 'Submitted', 'Deactivate'])
            ->where('e.BillDate', '!=', '0000-00-00')
            ->whereNotNull('e.BillDate')
            ->where('e.FinancedBy', '!=', '0');

        if ($departmentId) {
            $query->whereIn('dep.id', (array) $departmentId);
        }

        return $query
            ->groupBy('ct.ClaimName', 'ct.ClaimCode', 'dep.department_name', 'dep.department_code')
            ->select(
                'ct.ClaimName',
                'ct.ClaimCode',
                'dep.department_name',
                'dep.department_code',
                DB::raw("SUM(CASE WHEN e.ClaimYearId = {$yearId} THEN e.FinancedTAmt ELSE 0 END) as TotalFinancedTAmt_Y{$yearId}"),
                DB::raw("SUM(CASE WHEN e.ClaimYearId = {$previousYearId} THEN e.FinancedTAmt ELSE 0 END) as TotalFinancedTAmt_Y{$previousYearId}"),
                DB::raw("
                    ROUND(
                        CASE 
                            WHEN SUM(CASE WHEN e.ClaimYearId = {$previousYearId} THEN e.FinancedTAmt ELSE 0 END) = 0 
                            THEN NULL
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
            ->orderBy('ct.ClaimName')
            ->get();
    }
}
