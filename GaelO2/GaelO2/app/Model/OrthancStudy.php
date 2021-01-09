<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrthancStudy extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'orthanc_id';
    protected $keyType = 'string';
    public $incrementing = false;

    public function series(){
        return $this->hasMany('App\Model\OrthancSeries', 'orthanc_study_id', 'orthanc_id');
    }

    public function visit(){
        return $this->belongsTo('App\Model\Visit', 'visit_id', 'id');
    }


    public function uploader(){
        return $this->belongsTo('App\Model\User', 'user_id', 'id');
    }
}
