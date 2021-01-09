<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use SoftDeletes;

    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }

    public function visit(){
        return $this->belongsTo('App\Models\Visit', 'visit_id', 'id');
    }

}
