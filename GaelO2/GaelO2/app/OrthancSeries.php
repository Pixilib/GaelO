<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrthancSeries extends Model
{
    public function study(){
        $this->belongsTo('App\OrthancStudy', 'study_orthanc_id', 'orthanc_id');
    }
}
