<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Tracker extends Model
{

    public function user(){
        return $this->belongsTo('App\Model\User', 'user_id', 'id');
    }

    public function study(){
        return $this->belongsTo('App\Model\Study', 'study_name', 'name');
    }
}
