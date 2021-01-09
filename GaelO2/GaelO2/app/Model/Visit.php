<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function reviews(){
        return $this->hasMany('App\Model\Review', 'visit_id');
    }

    public function reviewStatus(){
        return $this->hasMany('App\Model\ReviewStatus', 'visit_id');
    }

    public function patient(){
        return $this->belongsTo('App\Model\Patient', 'patient_code', 'code');
    }

    public function visitType(){
        return $this->belongsTo('App\Model\VisitType', 'visit_type_id', 'id')->with('visitGroup');
    }

    public function creator(){
        return $this->belongsTo('App\Model\User', 'creator_user_id' , 'id');
    }

    public function controller(){
        return $this->belongsTo('App\Model\User', 'controller_user_id' , 'id');
    }

    public function correctiveActionUser(){
        return $this->belongsTo('App\Model\User', 'corrective_action_user_id' , 'id');
    }
}
