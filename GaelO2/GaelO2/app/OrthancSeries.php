<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrthancSeries extends Model
{
    protected $primaryKey = 'orthanc_id';
    protected $keyType = 'string';
    public $incrementing = false;

    public function orthancStudy(){
        return $this->belongsTo('App\OrthancStudy', 'orthanc_study_id', 'orthanc_id');
    }
}
