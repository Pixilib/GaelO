<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    public function center(){
        return $this->hasOne('App\Center', 'code', 'center_code');
    }

    public function study(){
        return $this->hasOne('App\Study', 'name', 'study_name');
    }
}
