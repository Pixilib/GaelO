<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use SoftDeletes, HasFactory;

    protected $guarded = [];

    public function reviews(){
        return $this->hasMany('App\Models\Review', 'visit_id');
    }

    public function reviewStatus(){
        return $this->hasMany('App\Models\ReviewStatus', 'visit_id');
    }

    public function patient(){
        return $this->belongsTo('App\Models\Patient', 'patient_code', 'code');
    }

    public function visitType(){
        return $this->belongsTo('App\Models\VisitType', 'visit_type_id', 'id')->with('visitGroup');
    }

    public function visitTypeOnly(){
        return $this->belongsTo('App\Models\VisitType', 'visit_type_id', 'id');
    }

    public function creator(){
        return $this->belongsTo('App\Models\User', 'creator_user_id' , 'id');
    }

    public function controller(){
        return $this->belongsTo('App\Models\User', 'controller_user_id' , 'id');
    }

    public function correctiveActionUser(){
        return $this->belongsTo('App\Models\User', 'corrective_action_user_id' , 'id');
    }
}
