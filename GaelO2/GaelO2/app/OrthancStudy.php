<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrthancStudy extends Model
{
    public function series(){
        $this->hasMany('App\OrthancSeries', 'orthanc_study_id', 'orthanc_id');
    }

    public function visit(){
        $this->belongsTo('App\Visit', 'visit_id', 'id');
    }

    public function uploader(){
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
