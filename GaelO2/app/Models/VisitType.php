<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitType extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'visit_group_id' => 'integer',
        'name' => 'string',
        'order' => 'integer',
        'local_form_needed' => 'boolean',
        'qc_probability' => 'integer',
        'review_probability' => 'integer',
        'optional'=> 'boolean',
        'limit_low_days' => 'integer',
        'limit_up_days' => 'integer',
        'anon_profile' => 'string',
        'dicom_constraints' => 'array',
    ];

    public function visits()
    {
        return $this->hasMany(Visit::class, 'visit_type_id');
    }

    public function visitGroup()
    {
        return $this->belongsTo(VisitGroup::class, 'visit_group_id', 'id');
    }
}
