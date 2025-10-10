<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HRMEmployeeGeneral extends Model
{
    protected $connection = 'hrims';
    protected $table = 'hrm_employee_general';
    protected $primaryKey = 'EmployeeID';
    public $timestamps = false;

    public function department()
    {
        return $this->belongsTo(CoreDepartments::class, 'DepartmentId', 'id');
    }
}
