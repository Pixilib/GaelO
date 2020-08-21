<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrthancSeries extends Model
{
    public function study(){
        $this->hasOne('App\OrthancStudy', 'study_orthanc_id');
    }
}
