<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use SoftDeletes;

    public function user(){
        return $this->belongsTo('App\Model\User', 'user_id', 'id');
    }

    public function visit(){
        return $this->belongsTo('App\Model\Visit', 'visit_id', 'id');
    }

}
