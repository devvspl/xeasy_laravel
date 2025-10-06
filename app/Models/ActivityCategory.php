<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityCategory extends Model
{
    protected $connection = 'expense';
    protected $table = 'adv_activity_categories';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'category_name',
        'mapped_activity',
        'description',
        'status',
    ];


    protected $casts = [
        'status' => 'boolean',
        'mapped_activity' => 'string',
    ];
}
