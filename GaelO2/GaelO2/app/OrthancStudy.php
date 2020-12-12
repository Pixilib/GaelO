<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrthancStudy extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'orthanc_id';
    protected $keyType = 'string';
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
