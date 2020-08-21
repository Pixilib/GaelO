<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    public function user(){
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function visit(){
        return $this->belongsTo('App\Visit', 'visit_id', 'id');
    }

}
