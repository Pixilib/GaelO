<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;

    public function center(){
        return $this->belongsTo('App\Model\Center', 'center_code', 'code');
    }

    public function study(){
        return $this->belongsTo('App\Model\Study', 'study_name', 'name');
    }

    public function visits(){
        return $this->hasMany('App\Model\Visit', 'patient_code', 'code');
    }
}
