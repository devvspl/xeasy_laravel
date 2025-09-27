<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseClaim;
use Illuminate\Support\Facades\DB;
use Storage;

class OdometerBackdateController extends Controller
{
    public function odoBackdateSubmission($yearId, $employeeId)
    {
        $employee = DB::connection('hrims')->table('hrm_employee_general')->where('EmployeeID', $employeeId)->select('DepartmentId', 'BUId')->first();
        if (!$employee) {
            return $this->jsonError('Employee not found.');
        }
        $departmentId = $employee->DepartmentId;
        $buId = $employee->BUId;
        if (!$departmentId || !$buId) {
            return $this->jsonError('Employee not found or department/BU not assigned.');
        }
        $backdateSetting = DB::connection('expense')->table('odo_backdate_settings')->where('department_id', $departmentId)->first();
        if (!$backdateSetting || !$backdateSetting->effective_date) {
            return $this->jsonError('Backdate setting not found for this department.');
        }
        if ($backdateSetting->is_active != 1) {
            return $this->jsonError('Backdate setting is not active for this department.');
        }
        $effectiveDate = $backdateSetting->effective_date;
        $approvalType = $backdateSetting->approval_type;
        $delayedDayAllowed = $backdateSetting->delayed_day ?? 0;
        $employeeIds = [];
        if ($approvalType === 'bu_level') {
            if (!$this->isBuGeneralManager($employeeId, $buId)) {
                return $this->jsonError('Employee is not a BU General Manager.');
            }
            $employeeIds = DB::connection('hrims')->table('hrm_employee_general')->where('BUId', $buId)->pluck('EmployeeID')->toArray();
        } elseif ($approvalType === 'hod_level') {
            if (!$this->isHod($employeeId, $departmentId)) {
                return $this->jsonError('Employee is not a Head of Department.');
            }
            $employeeIds = DB::connection('hrims')->table('hrm_employee_general')->where('DepartmentId', $departmentId)->pluck('EmployeeID')->toArray();
        } else {
            return $this->jsonError('Invalid approval type.');
        }
        if (empty($employeeIds)) {
            return $this->jsonError('No employees found in this BU or department.');
        }
        $model = new ExpenseClaim;
        $model->setTable(ExpenseClaim::tableName($yearId));
        $tableName = ExpenseClaim::tableName($yearId);
        $monthExpenseTable = "y{$yearId}_monthexpensefinal";
        $query = DB::table("{$tableName} as e")->join('hrims.hrm_employee as emp', 'emp.EmployeeID', '=', 'e.CrBy')->join('hrims.hrm_employee_general as gen', 'gen.EmployeeID', '=', 'e.CrBy')->leftJoin('hrims.core_departments as dep', 'gen.DepartmentId', '=', 'dep.id')->join("{$monthExpenseTable} as mef", function ($join) {
            $join->on('mef.EmployeeID', '=', 'e.CrBy')->on('mef.Month', '=', 'e.ClaimMonth')->where('mef.Status', '=', 'Open');
        })->select('e.CrBy', 'e.ExpId', 'e.ClaimYearId', 'e.BillDate', 'e.CrDate', 'e.opening_filepath', 'e.closing_filepath', 'e.odomtr_opening', 'e.odomtr_closing', 'e.TotKm', 'e.RatePerKM', 'e.ClaimStatus', 'e.Backdate_Odometer_Status', DB::raw('DATEDIFF(e.CrDate, e.BillDate) AS days_difference'), DB::raw("CONCAT(emp.Fname, ' ', emp.Sname, ' ', emp.Lname) AS employee_name"), 'emp.EmpCode', 'mef.Status as monthexpense_status', 'mef.Month as monthexpense_month')->where('e.ClaimId', 7)->whereColumn('e.BillDate', '!=', 'e.CrDate')->whereDate('e.BillDate', '>=', $effectiveDate)->whereIn('e.CrBy', $employeeIds)->whereNotNull('e.BillDate')->whereNotIn('e.ClaimStatus', ['Deactivate']);
        if ($delayedDayAllowed > 0) {
            $query->whereRaw('DATEDIFF(e.CrDate, e.BillDate) > ?', [$delayedDayAllowed]);
        }
        $records = $query->get();
        $grouped = $records->groupBy('CrBy')->map(function ($items) use ($approvalType) {
            $firstItem = $items->first();
            $statusCounts = ['Pending' => $items->where('Backdate_Odometer_Status', 'P')->count(), 'Approved' => $items->where('Backdate_Odometer_Status', 'A')->count(), 'Rejected' => $items->where('Backdate_Odometer_Status', 'R')->count(),];
            return ['approval_type' => $approvalType, 'month' => $firstItem->monthexpense_month ?? null, 'employee_name' => $firstItem->employee_name ?? null, 'EmpCode' => $firstItem->EmpCode ?? null, 'total_records' => $items->count(), 'status_count' => $statusCounts, 'items' => $items->map(function ($item) {
                $status = match ($item->Backdate_Odometer_Status) {
                    'P' => 'Pending',
                    'A' => 'Approved',
                    'R' => 'Rejected',
                    default => 'Unknown',
                };
                return ['ExpId' => $item->ExpId, 'BillDate' => $item->BillDate, 'CrDate' => $item->CrDate, 'odomtr_opening' => $item->odomtr_opening, 'odomtr_closing' => $item->odomtr_closing, 'opening_filepath' => Storage::disk('s3')->url("Expense/{$item->ClaimYearId}/{$item->CrBy}/{$item->opening_filepath}"), 'closing_filepath' => Storage::disk('s3')->url("Expense/{$item->ClaimYearId}/{$item->CrBy}/{$item->closing_filepath}"), 'TotKm' => $item->TotKm, 'RatePerKM' => $item->RatePerKM, 'ClaimStatus' => $item->ClaimStatus, 'days_difference' => $item->days_difference, 'Backdate_Odometer_Status' => $status,];
            })->toArray(),];
        });
        return $this->jsonSuccess($grouped, 'Odometer backdate submission retrieved successfully.');
    }
    public function odoBackdateSubmissionBtnShow($yearId, $employeeId)
    {
        $employee = DB::connection('hrims')
            ->table('hrm_employee_general')
            ->where('EmployeeID', $employeeId)
            ->select('DepartmentId', 'BUId')
            ->first();

        if (!$employee) {
            return $this->jsonError('Employee not found.');
        }

        $departmentId = $employee->DepartmentId;
        $buId         = $employee->BUId;

        if (!$departmentId || !$buId) {
            return $this->jsonError('Employee not found or department/BU not assigned.');
        }

        $backdateSetting = DB::connection('expense')
            ->table('odo_backdate_settings')
            ->where('department_id', $departmentId)
            ->first();

        if (!$backdateSetting || !$backdateSetting->effective_date) {
            return $this->jsonError('Backdate setting not found for this department.');
        }

        if ($backdateSetting->is_active != 1) {
            return $this->jsonError('Backdate setting is not active for this department.');
        }

        $effectiveDate      = $backdateSetting->effective_date;
        $approvalType       = $backdateSetting->approval_type;
        $delayedDayAllowed  = $backdateSetting->delayed_day ?? 0;


        if ($approvalType === 'bu_level') {
            if (!$this->isBuGeneralManager($employeeId, $buId)) {
                return $this->jsonError('Employee is not a BU General Manager.');
            }
            $employeeIds = DB::connection('hrims')
                ->table('hrm_employee_general')
                ->where('BUId', $buId)
                ->pluck('EmployeeID')
                ->toArray();
        } elseif ($approvalType === 'hod_level') {
            if (!$this->isHod($employeeId, $departmentId)) {
                return $this->jsonError('Employee is not a Head of Department.');
            }
            $employeeIds = DB::connection('hrims')
                ->table('hrm_employee_general')
                ->where('DepartmentId', $departmentId)
                ->pluck('EmployeeID')
                ->toArray();
        } else {
            return $this->jsonError('Invalid approval type.');
        }

        if (empty($employeeIds)) {
            return $this->jsonError('No employees found in this BU or department.');
        }


        $tableName         = ExpenseClaim::tableName($yearId);
        $monthExpenseTable = "y{$yearId}_monthexpensefinal";


        $query = DB::table("{$tableName} as e")
            ->join("{$monthExpenseTable} as mef", function ($join) {
                $join->on('mef.EmployeeID', '=', 'e.CrBy')
                    ->on('mef.Month', '=', 'e.ClaimMonth')
                    ->where('mef.Status', '=', 'Open');
            })
            ->whereColumn('e.BillDate', '!=', 'e.CrDate')
            ->whereDate('e.BillDate', '>=', $effectiveDate)
            ->whereIn('e.CrBy', $employeeIds)
            ->whereNotNull('e.BillDate')
            ->where('e.Backdate_Odometer_Status', '=', 'P')
            ->whereNotIn('e.ClaimStatus', ['Deactivate']);

        if ($delayedDayAllowed > 0) {
            $query->whereRaw('DATEDIFF(e.CrDate, e.BillDate) > ?', [$delayedDayAllowed]);
        }

        $pendingCount = $query->count();
        $buttonShow   = $pendingCount > 0;

        return $this->jsonSuccess([
            'pending_count' => $pendingCount,
            'show_button'   => $buttonShow,
        ], 'Odometer backdate pending count retrieved successfully.');
    }

    public function odoBackdateApprove($yearId, $expenseId, $employeeId)
    {
        $model = new ExpenseClaim;
        $model->setTable(ExpenseClaim::tableName($yearId));
        $record = $model->where('ExpId', $expenseId)->first();
        if (!$record) {
            return $this->jsonError('Expense claim not found.');
        }
        if ($record->Backdate_Odometer_Status === 'A') {
            return $this->jsonError('Expense claim is already approved.');
        }
        if ($record->Backdate_Odometer_Status === 'R') {
            return $this->jsonError('Expense claim is already rejected and cannot be approved.');
        }
        $record->Backdate_Odometer_By = $employeeId;
        $record->Backdate_Odometer_Date = date('Y-m-d');
        $record->Backdate_Odometer_Status = 'A';
        $record->save();
        return $this->jsonSuccess($record, 'Odometer backdate approved successfully.');
    }
    public function odoBackdateReject($yearId, $expenseId, $employeeId)
    {
        $model = new ExpenseClaim;
        $model->setTable(ExpenseClaim::tableName($yearId));
        $record = $model->where('ExpId', $expenseId)->first();
        if (!$record) {
            return $this->jsonError('Expense claim not found.');
        }
        if ($record->Backdate_Odometer_Status === 'R') {
            return $this->jsonError('Expense claim is already rejected.');
        }
        if ($record->Backdate_Odometer_Status === 'A') {
            return $this->jsonError('Expense claim is already approved and cannot be rejected.');
        }
        $record->Backdate_Odometer_Status = 'R';
        $record->Backdate_Odometer_By = $employeeId;
        $record->Backdate_Odometer_Date = date('Y-m-d');
        $record->save();
        return $this->jsonSuccess($record, 'Odometer backdate rejected successfully.');
    }
    public function odoBackdateBulkApproval($yearId, $employeeId)
    {
        $model = new ExpenseClaim;
        $model->setTable(ExpenseClaim::tableName($yearId));
        $updatedCount = $model->where('ClaimId', 7)->where('Backdate_Odometer_Status', 'P')->update(['Backdate_Odometer_Status' => 'A', 'Backdate_Odometer_By' => $employeeId, 'Backdate_Odometer_Date' => date('Y-m-d'),]);
        return $this->jsonSuccess(['updated_count' => $updatedCount], 'Bulk odometer backdate approval completed successfully.');
    }
    public function odoBackdateBulkRejection($yearId, $employeeId)
    {
        $model = new ExpenseClaim;
        $model->setTable(ExpenseClaim::tableName($yearId));
        $updatedCount = $model->where('ClaimId', 7)->where('Backdate_Odometer_Status', 'P')->update(['Backdate_Odometer_Status' => 'R', 'Backdate_Odometer_By' => $employeeId, 'Backdate_Odometer_Date' => date('Y-m-d'),]);
        return $this->jsonSuccess(['updated_count' => $updatedCount], 'Bulk odometer backdate rejection completed successfully.');
    }
    private function isBuGeneralManager($employeeId, $buId)
    {
        $employee = DB::connection('hrims')->table('hrm_employee_general')->where('EmployeeID', $employeeId)->where('TerrId', 0)->where('ZoneId', 0)->where('RegionId', 0)->where('BUId', '>', 0)->where('BUId', $buId)->first();
        if (!$employee) {
            return false;
        }
        return $employee && !empty($employee->BUId);
    }
    private function isHod($employeeId, $departmentId)
    {
        $employees = DB::connection('hrims')->table('hrm_employee_general')->where('DepartmentId', $departmentId)->select('EmployeeID')->get();
        if ($employees->isEmpty()) {
            return false;
        }
        $topLevelEmployee = $employees->firstWhere('EmployeeID', null);
        if ($topLevelEmployee) {
            return $topLevelEmployee->EmployeeID == $employeeId;
        }
        $employeeMap = $employees->pluck('EmployeeID')->toArray();
        $validEmployeeIds = $employees->pluck('EmployeeID')->toArray();
        return $this->isTopInHierarchy($employeeId, $employeeMap, $validEmployeeIds);
    }
    private function isTopInHierarchy($employeeId, $employeeMap, $validEmployeeIds)
    {
        if (!isset($employeeMap[$employeeId]) || $employeeMap[$employeeId] === null || !in_array($employeeMap[$employeeId], $validEmployeeIds)) {
            return true;
        }
        $hasSubordinates = in_array($employeeId, array_values($employeeMap));
        if (!$hasSubordinates) {
            return false;
        }
        $managerId = $employeeMap[$employeeId];
        return $this->isTopInHierarchy($managerId, $employeeMap, $validEmployeeIds);
    }
}
