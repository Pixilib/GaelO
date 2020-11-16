<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrthancStudy extends Model
{

    protected $primaryKey = 'orthanc_id';
    public $incrementing = false;

    public function series(){
        return $this->hasMany('App\OrthancSeries', 'orthanc_study_id', 'orthanc_id');
    }

    public function visit(){
        return $this->belongsTo('App\Visit', 'visit_id', 'id');
    }

    public function uploader(){
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
