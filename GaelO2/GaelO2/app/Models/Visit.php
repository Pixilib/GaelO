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
        return $this->hasMany(Review::class, 'visit_id');
    }

    public function reviewStatus(){
        return $this->hasOne(ReviewStatus::class, 'visit_id');
    }

    public function patient(){
        return $this->belongsTo(Patient::class, 'patient_code', 'code');
    }

    public function visitType(){
        return $this->belongsTo(VisitType::class, 'visit_type_id', 'id')->with('visitGroup');
    }

    public function visitTypeOnly(){
        return $this->belongsTo(VisitType::class, 'visit_type_id', 'id');
    }

    public function creator(){
        return $this->belongsTo(User::class, 'creator_user_id' , 'id');
    }

    public function controller(){
        return $this->belongsTo(User::class, 'controller_user_id' , 'id');
    }

    public function correctiveActionUser(){
        return $this->belongsTo(User::class, 'corrective_action_user_id' , 'id');
    }
}
