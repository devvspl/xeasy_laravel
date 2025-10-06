<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseHeadMapping extends Model
{
    protected $connection = 'expense';
    protected $table = 'adv_expense_head_name_mapping';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'activity_id',
        'expense_head_id',
        'checked',
    ];

    protected $casts = [
        'checked' => 'boolean',
    ];

public function activity()
{
    return $this->belongsTo(ActivityName::class, 'activity_id', 'id');
}

public function expenseHead()
{
    return $this->belongsTo(ExpenseHeadCategory::class, 'expense_head_id', 'id');
}

}