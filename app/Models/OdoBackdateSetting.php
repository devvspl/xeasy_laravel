<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OdoBackdateSetting extends Model
{
    use HasFactory;

    protected $table = 'odo_backdate_settings';

    protected $fillable = ['department_id', 'is_active', 'approval_type'];
}
