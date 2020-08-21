<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Documentation extends Model
{
    public function study(){
        return $this->belongsTo('App\Study', 'study_name', 'name');
    }
}
