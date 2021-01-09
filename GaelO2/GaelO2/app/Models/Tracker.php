<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tracker extends Model
{

    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }

    public function study(){
        return $this->belongsTo('App\Models\Study', 'study_name', 'name');
    }
}
