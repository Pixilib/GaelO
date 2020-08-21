<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Documentation extends Model
{
    public function study(){
        return $this->hasOne('App\Study', 'name', 'study_name');
    }
}
