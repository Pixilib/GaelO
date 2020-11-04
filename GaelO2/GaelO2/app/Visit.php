<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $guarded = [];

    public function reviews(){
        return $this->hasMany('App\Review', 'visit_id');
    }

    public function reviewStatus(){
        return $this->hasMany('App\ReviewStatus', 'visit_id');
    }

    public function patient(){
        return $this->belongsTo('App\Patient', 'patient_code', 'code');
    }

    public function visitType(){
        return $this->belongsTo('App\VisitType', 'visit_type_id', 'id');
    }

    public function creator(){
        return $this->belongsTo('App\User', 'creator_user_id' , 'id');
    }

    public function controller(){
        return $this->belongsTo('App\User', 'controller_user_id' , 'id');
    }

    public function correctiveActionUser(){
        return $this->belongsTo('App\User', 'corrective_action_user_id' , 'id');
    }
}
