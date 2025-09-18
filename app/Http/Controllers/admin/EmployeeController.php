<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function search(Request $request)
    {
        $searchTerm = $request->input('search', '');
        $status = $request->input('status', '');
        $page = $request->input('page', 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $companyId = session('company_id');
        $query = DB::connection('hrims')->table('hrm_employee AS emp')->select(DB::raw("CONCAT_WS(' ', emp.Fname, emp.Sname, emp.Lname) AS EmployeeName"), 'emp.EmployeeID', 'emp.EmpCode', 'cg.grade_name', 'cd.department_name', 'emp.EmpStatus', DB::raw("CONCAT('https://vnrseeds.co.in/file-view/Employee_Image/', '$companyId', '/', emp.EmpCode, '.jpg') AS image_url"))->leftJoin('hrm_employee_general AS eg', 'emp.EmployeeID', '=', 'eg.EmployeeID')->leftJoin('core_departments AS cd', 'cd.id', '=', 'eg.DepartmentId')->leftJoin('core_grades AS cg', 'cg.id', '=', 'eg.GradeId')->where('emp.companyid', $companyId);
        if (! empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw("CONCAT_WS(' ', emp.Fname, emp.Sname, emp.Lname) LIKE ?", ['%'.$searchTerm.'%'])->orWhere('emp.Fname', 'LIKE', '%'.$searchTerm.'%')->orWhere('emp.Lname', 'LIKE', '%'.$searchTerm.'%')->orWhere('emp.EmpCode', 'LIKE', '%'.$searchTerm.'%')->orWhere('cd.department_name', 'LIKE', '%'.$searchTerm.'%')->orWhere('cg.grade_name', 'LIKE', '%'.$searchTerm.'%');
            });
        }
        if (! empty($status)) {
            $query->where('emp.EmpStatus', $status);
        }
        $total = (clone $query)->count();
        $employees = $query->orderBy('emp.EmployeeID')->offset($offset)->limit($limit)->get();

        return response()->json(['employees' => $employees, 'has_more' => ($offset + $limit) < $total]);
    }

    public function employee(Request $request)
    {
        return view('admin.employee');
    }
}
