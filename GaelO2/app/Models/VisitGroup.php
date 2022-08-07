<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitGroup extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'study_name' => 'string',
        'name' => 'string',
        'modality' => 'string'
    ];

    public function study()
    {
        return $this->belongsTo(Study::class, 'study_name', 'name');
    }

    public function visitTypes()
    {
        return $this->hasMany(VisitType::class, 'visit_group_id');
    }
}
