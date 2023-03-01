<?php

namespace App\Models;

use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Constants\Enums\UploadStatusEnum;
use App\GaelO\Constants\Enums\VisitStatusDoneEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use SoftDeletes, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'creator_user_id' => 'integer',
        'creation_date' => 'datetime',
        'patient_id' => 'string',
        'visit_date' => 'date',
        'visit_type_id' => 'integer',
        'status_done' => VisitStatusDoneEnum::class,
        'reason_for_not_done' => 'string',
        'upload_status' => UploadStatusEnum::class,
        'state_investigator_form' => InvestigatorFormStateEnum::class,
        'state_quality_control' => QualityControlStateEnum::class,
        'controller_user_id' => 'integer',
        'control_date' => 'datetime',
        'image_quality_control' => 'boolean',
        'form_quality_control' => 'boolean',
        'image_quality_comment' => 'string',
        'form_quality_comment' => 'string',
        'corrective_action_user_id' => 'integer',
        'corrective_action_date' => 'datetime',
        'corrective_action_new_upload' => 'boolean',
        'corrective_action_investigator_form' => 'boolean',
        'corrective_action_comment' => 'string',
        'corrective_action_applied' => 'boolean'
    ];

    public function reviews()
    {
        return $this->hasMany(Review::class, 'visit_id');
    }


    /**
     * Add default relation as record not always existing in ancillary studies
     */
    public function reviewStatus()
    {
        return $this->hasOne(ReviewStatus::class, 'visit_id', 'id')->withDefault(function ($reviewStatus, $visit) {
            $reviewStatus->review_available = null;
            $reviewStatus->target_lesions = null;
            $reviewStatus->review_status = null;
            $reviewStatus->review_conclusion_value = null;
            $reviewStatus->review_conclusion_date = null;
        });
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
