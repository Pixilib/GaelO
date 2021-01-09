<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = [];

    public function user(){
        return $this->belongsTo('App\Model\User', 'user_id' , 'id');
    }
}
