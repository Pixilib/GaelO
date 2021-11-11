<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;

    public function center(){
        return $this->belongsTo(Center::class, 'center_code', 'code');
    }

    public function study(){
        return $this->belongsTo(Study::class, 'study_name', 'name');
    }

    public function visits(){
        return $this->hasMany(Visit::class, 'patient_id', 'id');
    }
}
