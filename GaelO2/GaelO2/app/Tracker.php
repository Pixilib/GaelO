<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tracker extends Model
{

    public function user(){
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function study(){
        return $this->belongsTo('App\Study', 'study_name', 'name');
    }
}
