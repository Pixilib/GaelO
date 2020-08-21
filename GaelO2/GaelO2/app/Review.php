<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    public function user(){
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function visit(){
        return $this->hasOne('App\Visit', 'id', 'visit_id');
    }

}
