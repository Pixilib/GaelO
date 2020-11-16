<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrthancSeries extends Model
{
    protected $primaryKey = 'orthanc_id';
    public $incrementing = false;

    public function study(){
        return $this->belongsTo('App\OrthancStudy', 'study_orthanc_id', 'orthanc_id');
    }
}
