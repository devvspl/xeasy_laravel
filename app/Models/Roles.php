<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Roles extends SpatieRole
{
    protected $fillable = [
        'name',
        'status',
        'created_by',
        'updated_by',
        'guard_name',
        'created_at',
        'updated_at',
    ];
}
