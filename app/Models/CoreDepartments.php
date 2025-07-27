<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreDepartments extends Model
{
    protected $connection = 'hrims';
    protected $table = 'core_departments';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['id', 'department_name', 'department_code', 'numeric_code', 'effective_date', 'is_active'];

    /**
     * Define the relationship with the HRMEmployees model.
     * One department can have many employees.
     */
    public function employees()
    {
        return $this->hasMany(HRMEmployees::class, 'DepartmentId', 'id')
                    ->join('hrm_employee_general', 'hrm_employee.EmployeeID', '=', 'hrm_employee_general.EmployeeID');
    }
}