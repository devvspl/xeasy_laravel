<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HRMEmployees extends Model
{
    protected $connection = 'hrims';
    protected $table = 'hrm_employee';
    protected $primaryKey = 'EmployeeID';
    public $timestamps = false;
    protected $fillable = ['EmployeeID', 'EmpCode_New', 'EmpCode', 'Fname', 'Sname', 'Lname', 'EmpStatus'];

    public function department()
    {
        return $this->belongsTo(CoreDepartments::class, 'DepartmentId', 'DepartmentId')->join('hrm_employee_general', 'hrm_employee.EmployeeID', '=', 'hrm_employee_general.EmployeeID');
    }
}