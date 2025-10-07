<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreVertical extends Model
{
    protected $connection = 'hrims';
    protected $table = 'core_verticals';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'vertical_name',
        'vertical_code',
        'effective_date',
        'is_active',
    ];
    public static function active()
    {
        return self::where('is_active', 1)->orderBy('vertical_name')->get(['id', 'vertical_name as name']);
    }
}
