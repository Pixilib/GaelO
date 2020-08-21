<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrthancStudy extends Model
{
    public function series(){
        $this->hasMany('App\OrthancSeries', 'orthanc_study_id', 'orthanc_id');
    }

    public function visit(){
        $this->hasOne('App\Visit', 'id', 'visit_id');
    }

    public function uploader(){
        return $this->hasOne('App\User', 'id', 'user_id');
    }
}
