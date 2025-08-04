<?php
namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class MediatorController extends Controller
{
    public function dataPunch(Request $request)
    {
        $status = $request->input('status', 'submitted');
        $selectedDate = $request->input('date');
        $selectedEmp = $request->input('emp');
        $fYearId = session('year_id');
        $companyId = session('CompanyId');
        $today = now()->format('Y-m-d');
        $monthStart = now()->startOfMonth()->format('Y-m-d');
        $table = "y{$fYearId}_expenseclaims";

        $baseCondition = function ($query) use ($today, $companyId) {
            $query->where(function ($q) use ($today, $companyId) {
                $q->where('CrDate', '<=', $today)
                    ->orWhere('ClaimId', '!=', 7)
                    ->orWhereRaw("? = 3", [$companyId]);
            });
        };

        $commonQuery = DB::table($table)
            ->where('ClaimStatus', '!=', 'Deactivate')
            ->where('AttachTo', 0)
            ->where($baseCondition);

        // Status-specific filters
        if ($status === 'hold') {
            $commonQuery->where('ClaimStatus', 'Submitted')->where('Rmk', 1);
        } elseif ($status === 'draft') {
            $commonQuery->where('ClaimStatus', 'Draft')->where('Filledokay', '!=', 2);
        } elseif ($status === 'filled') {
            $commonQuery->where('ClaimStatus', 'Filled')->where('Filledokay', '!=', 2)->where('BillDate', '>=', $monthStart);
        } elseif ($status === 'uploaded') {
            $commonQuery->where('ClaimStatus', 'Submitted')->where('Rmk', 0);
        } elseif ($status === 'denied') {
            $commonQuery->where('ClaimStatus', 'Filled')->where('FilledOkay', 2)->where('FilledBy', '>', 0);
        }

        // Apply Date Filter
        if ($selectedDate && $selectedDate != '1010') {
            $commonQuery->whereDate('CrDate', $selectedDate);
        }

        // Apply Employee Filter
        if ($selectedEmp && $selectedEmp != '1010') {
            $commonQuery->where('CrBy', $selectedEmp);
        }

        // Fetch detailed data
        $punchData = $commonQuery->select('ExpId', 'CrBy', 'CrDate', 'DateEntryRemark')
            ->get()
            ->map(function ($exp) {
                $exp->CrDateFormatted = \Carbon\Carbon::parse($exp->CrDate)->format('d-m-Y');
                return $exp;
            });
        // $punchData = [];

        // Counts by CrBy (Employee)
        $countByEmployee = $commonQuery->select('CrBy', DB::raw('COUNT(*) as count'))
            ->groupBy('CrBy')
            ->get();

        // Counts by CrDate (Date)
        $countByDate = $commonQuery->select('CrDate', DB::raw('COUNT(*) as count'))
            ->groupBy('CrDate')
            ->get();

        // Counts
        $holdCount = DB::table($table)
            ->where('ClaimStatus', 'Submitted')
            ->where('Rmk', 1)
            ->where('AttachTo', 0)
            ->where($baseCondition)
            ->count();

        $draftCount = DB::table($table)
            ->where('ClaimStatus', 'Draft')
            ->where('Filledokay', '!=', 2)
            ->where('AttachTo', 0)
            ->where($baseCondition)
            ->count();

        $filledCount = DB::table($table)
            ->where('ClaimStatus', 'Filled')
            ->where('ClaimStatus', '!=', 'Deactivate')
            ->where('Filledokay', '!=', 2)
            ->where('AttachTo', 0)
            ->where('BillDate', '>=', $monthStart)
            ->where($baseCondition)
            ->count();

        $uploadedCount = DB::table($table)
            ->where('ClaimStatus', 'Submitted')
            ->where('ClaimStatus', '!=', 'Deactivate')
            ->where('Rmk', 0)
            ->where('AttachTo', 0)
            ->where($baseCondition)
            ->count();

        $deniedCount = DB::table($table)
            ->where('ClaimStatus', 'Filled')
            ->where('ClaimYearId', $fYearId)
            ->where('ClaimStatus', '!=', 'Deactivate')
            ->where('FilledOkay', 2)
            ->where('FilledBy', '>', 0)
            ->where('AttachTo', 0)
            ->where($baseCondition)
            ->count();

        // For Select dropdowns
        $availableDates = DB::table($table)
            ->select('CrDate')
            ->where('ClaimStatus', '!=', 'Deactivate')
            ->where($baseCondition)
            ->groupBy('CrDate')
            ->orderByDesc('CrDate')
            ->pluck('CrDate');

        $availableEmployees = DB::table($table)
            ->select('CrBy')
            ->where('ClaimStatus', '!=', 'Deactivate')
            ->where($baseCondition)
            ->groupBy('CrBy')
            ->orderBy('CrBy')
            ->pluck('CrBy');

        $employees = DB::connection('hrims')
            ->table('hrm_employee')
            ->whereIn('EmployeeID', $availableEmployees)
            ->select('EmployeeID', DB::raw("CONCAT(Fname, ' ', COALESCE(Sname, ''), ' ', Lname) as EmpName"), 'EmpCode')
            ->get();

        return view('admin.data_punch', compact('status', 'punchData', 'holdCount', 'draftCount', 'filledCount', 'uploadedCount', 'deniedCount', 'availableDates', 'employees', 'selectedDate', 'selectedEmp', 'countByEmployee', 'countByDate'));
    }
}
