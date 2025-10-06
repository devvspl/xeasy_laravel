<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseHeadCategory extends Model
{
    protected $connection = 'expense'; 
    protected $table = 'adv_expense_head_category';
    protected $primaryKey = 'id';
    public $timestamps = true; 
    protected $fillable = [
        'expense_head_name',
        'short_code',
        'field_type',
        'has_file',
        'file_required',
        'status',
    ];

    
    protected $casts = [
        'has_file' => 'boolean',
        'file_required' => 'boolean',
        'status' => 'boolean',
    ];
}