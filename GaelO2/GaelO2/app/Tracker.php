<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tracker extends Model
{
    protected $primaryKey = ['date', 'user_id'];
    public $incrementing = false;

    public function user(){
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function study(){
        return $this->belongsTo('App\Study', 'study_name', 'name');
    }
}
