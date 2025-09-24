<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Monthexpensefinal extends Model
{
    protected $connection = 'expense';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $table;

    protected $fillable = [
        'DocId',
        'DocNo',
        'EmployeeID',
        'Month',
        'YearId',
        'AllowSubmit',
        'Status',
        'SubPermission',
        'Crdate',
        'DateOfSubmit',
        'Km_Confirmation',
        'Total_Claim',
        'Claim_Amount',
        'Verified_Amount',
        'Verified_Date',
        'Approved_Amount',
        'Approved_Date',
        'Finance_Amount',
        'Finance_Date',
        'Move_FocuseSheet',
        'Fin_AppBy',
        'Fin_AppDate',
        'Fin_AdvancePay',
        'Fin_PayOption',
        'Fin_PayBy',
        'Fin_PayAmt',
        'Fin_PayDate',
        'Fin_PayRemark',
        'Fin_PayRemark_1',
        'Fin_PayRemark_2',
        'PostDate',
        'DocateNo',
        'Agency',
        'CuImag',
        'RecevingDate',
        'VerifDate',
        'CNIssueDate',
        'DocRmk',
    ];

    public static function tableName(int $yearId): string
    {
        return 'y'.$yearId.'_monthexpensefinal';
    }
}
