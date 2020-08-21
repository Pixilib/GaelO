<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    public function reviews(){
        $this->hasMany('App\Review', 'visit_id');
    }

    public function patient(){
        $this->hasOne('App\Patient', 'code', 'patient_code');
    }

    public function visitType(){
        $this->hasOne('App\VisitType', 'id', 'visit_type_id');
    }

    public function creator(){
        $this->hasOne('App\User', 'id', 'creator_user_id');
    }

    public function controller(){
        $this->hasOne('App\User', 'id', 'controller_user_id');
    }

    public function correctiveActionUser(){
        $this->hasOne('App\User', 'id', 'corrective_action_user_id');
    }
}
