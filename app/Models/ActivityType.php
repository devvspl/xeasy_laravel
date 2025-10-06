<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityType extends Model
{
    protected $connection = 'expense'; // Consistent with previous code
    protected $table = 'adv_activity_types';
    protected $primaryKey = 'id';
    public $timestamps = true; // Since the table has created_at and updated_at
    protected $fillable = [
        'category_id',
        'department_id',
        'type_name',
        'description',
        'status',
    ];

    // Cast attributes to specific types
    protected $casts = [
        'status' => 'boolean',
        'department_id' => 'string', // Treated as a string for comma-separated department IDs
    ];

    // Relationship with ActivityCategory
    public function category()
    {
        return $this->belongsTo(ActivityCategory::class, 'category_id', 'id');
    }

    // Accessor to get department IDs as an array
    public function getDepartmentIdsAttribute()
    {
        return $this->department_id ? explode(',', $this->department_id) : [];
    }

    // Mutator to set department IDs as a comma-separated string
    public function setDepartmentIdsAttribute($value)
    {
        $this->attributes['department_id'] = is_array($value) ? implode(',', $value) : $value;
    }
}