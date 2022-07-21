<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use SoftDeletes, HasFactory;

    protected $guarded = [];

    public function reviews()
    {
        return $this->hasMany(Review::class, 'visit_id');
    }


    /*
    ->withDefault([
            'review_available' => false,
            'target_lesions' => null,
            'review_status' => 'Not Done',
            'review_conclusion_value' => null,
            'review_conclusion_date'=>null
        ]);;*/

    public function reviewStatus()
    {
        return $this->hasOne(ReviewStatus::class, 'visit_id', 'id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'id');
    }

    public function visitGroup()
    {
        return $this->belongsTo(VisitGroup::class, "visit_group_id");
    }

    public function visitType()
    {
        return $this->belongsTo(VisitType::class, 'visit_type_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_user_id', 'id')->withTrashed();
    }

    public function controller()
    {
        return $this->belongsTo(User::class, 'controller_user_id', 'id')->withTrashed();
    }

    public function correctiveActionUser()
    {
        return $this->belongsTo(User::class, 'corrective_action_user_id', 'id')->withTrashed();
    }

    public function dicomStudies()
    {
        return $this->hasMany(DicomStudy::class, 'visit_id', 'id');
    }
}
