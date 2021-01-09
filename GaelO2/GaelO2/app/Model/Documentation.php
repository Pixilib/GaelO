<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Documentation extends Model
{
    public function study(){
        return $this->belongsTo('App\Model\Study', 'study_name', 'name');
    }
}
