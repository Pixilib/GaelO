<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;

    public function center(){
        return $this->belongsTo('App\Models\Center', 'center_code', 'code');
    }

    public function study(){
        return $this->belongsTo('App\Models\Study', 'study_name', 'name');
    }

    public function visits(){
        return $this->hasMany('App\Models\Visit', 'patient_code', 'code');
    }
}
