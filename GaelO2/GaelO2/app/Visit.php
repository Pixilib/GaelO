<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    public function reviews(){
        $this->hasMany('App\Review', 'visit_id');
    }

    public function patient(){
        $this->belongsTo('App\Patient', 'patient_code', 'code');
    }

    public function visitType(){
        $this->belongsTo('App\VisitType', 'visit_type_id', 'id');
    }

    public function creator(){
        $this->belongsTo('App\User', 'creator_user_id' , 'id');
    }

    public function controller(){
        $this->belongsTo('App\User', 'controller_user_id' , 'id');
    }

    public function correctiveActionUser(){
        $this->belongsTo('App\User', 'corrective_action_user_id' , 'id');
    }
}
