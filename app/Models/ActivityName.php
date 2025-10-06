<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityName extends Model
{
    protected $connection = 'expense';
    protected $table = 'adv_activity_names';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'category_id',
        'activity_name', // Stores ClaimId from claimtype
        'description',
        'dept_id',
        'vertical',
        'from_month',
        'to_month',
        'from_year',
        'to_year',
        'approved_limit',
        'approved_amount',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'dept_id' => 'string',
        'vertical' => 'string',
        'from_month' => 'date',
        'to_month' => 'date',
        'approved_limit' => 'decimal:0',
        'approved_amount' => 'integer',
    ];

    // Relationship with ActivityCategory
    public function category()
    {
        return $this->belongsTo(ActivityCategory::class, 'category_id', 'id');
    }

    // Relationship with ClaimType
    public function claimType()
    {
        return $this->belongsTo(ClaimType::class, 'activity_name', 'ClaimId');
    }

    // Accessor for dept_id as an array
    public function getDepartmentIdsAttribute()
    {
        return $this->dept_id ? explode(',', $this->dept_id) : [];
    }

    // Mutator for dept_id
    public function setDepartmentIdsAttribute($value)
    {
        $this->attributes['dept_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    // Accessor for vertical as an array
    public function getVerticalsAttribute()
    {
        return $this->vertical ? explode(',', $this->vertical) : [];
    }

    // Mutator for vertical
    public function setVerticalsAttribute($value)
    {
        $this->attributes['vertical'] = is_array($value) ? implode(',', $value) : $value;
    }

    // Accessor to get ClaimName for display
    public function getClaimNameAttribute()
    {
        return $this->claimType ? $this->claimType->ClaimName : 'N/A';
    }
}