<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Study extends Model
{
    public function patients(){
        $this->hasMany('App\Patient', 'study_name');
    }
}
