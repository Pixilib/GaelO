<?php

namespace App\GaelO\UseCases\GetVisit;

class VisitEntity {
    public ?int $creatorUserId;
    public ?string $creationDate;
    public int $patientCode;
    public ?string $visitDate;
    public int $visitTypeId;
    public string $statusDone;
    public ?string $reasonForNotDone;
    public string $uploadStatus;
    public string $stateInvestigatorForm;
    public string $stateQualityControl;
    public ?int $controllerUserId;
    public ?string $controlDate;
    public bool $imageQualityControl;
    public bool $formQualityControl;
    public ?string $imageQualityComment;
    public ?string $formQualityComment;
    public ?int $correctiveActionUserId;
    public ?string $correctiveActionDate;
    public bool $correctiveActionNewUpload;
    public ?bool $correctiveActionInvestigatorForm;
    public ?string $correctiveActionOther;
    public ?bool $correctiveActionDecision;
    public ?string $lastReminderUpload;

    public ?string $visitGroupModality;
    public ?string $visitTypeName;
    public ?int $visitTypeOrder;
    public ?bool $visitTypeOptional;
    public ?int $visitGroupId;

    public ?string $reviewStatus;
    public ?string $reviewConclusionValue;
    public ?string $reviewConclusionDate;

    public static function fillFromDBReponseArray(array $array){
        $visitEntity  = new VisitEntity();
        $visitEntity->id = $array['id'];
        $visitEntity->creatorUserId = $array['creator_user_id'];
        $visitEntity->creationDate = $array['creation_date'];
        $visitEntity->patientCode = $array['patient_code'];
        $visitEntity->visitDate = $array['visit_date'];
        $visitEntity->visitTypeId = $array['visit_type_id'];
        $visitEntity->statusDone = $array['status_done'];
        $visitEntity->reasonForNotDone = $array['reason_for_not_done'];
        $visitEntity->uploadStatus = $array['upload_status'];
        $visitEntity->stateInvestigatorForm = $array['state_investigator_form'];
        $visitEntity->stateQualityControl = $array['state_quality_control'];
        $visitEntity->controllerUserId = $array['controller_user_id'];
        $visitEntity->controlDate = $array['control_date'];
        $visitEntity->imageQualityControl = $array['image_quality_control'];
        $visitEntity->formQualityControl = $array['form_quality_control'];
        $visitEntity->imageQualityComment = $array['image_quality_comment'];
        $visitEntity->formQualityComment = $array['form_quality_comment'];
        $visitEntity->correctiveActionUserId = $array['corrective_action_user_id'];
        $visitEntity->correctiveActionDate = $array['corrective_action_date'];
        $visitEntity->correctiveActionNewUpload = $array['corrective_action_new_upload'];
        $visitEntity->correctiveActionInvestigatorForm = $array['corrective_action_investigator_form'];
        $visitEntity->correctiveActionOther = $array['corrective_action_comment'];
        $visitEntity->correctiveActionDecision = $array['corrective_action_applyed'];
        $visitEntity->lastReminderUpload = $array['last_reminder_upload'];
        return $visitEntity;
    }

    public function setVisitContext(string $visitGroupModality,
                                string $visitTypeName,
                                int $visitTypeOrder,
                                bool $visitTypeOptional,
                                int $visitGroupId){

        $this->visitGroupModality = $visitGroupModality;
        $this->visitTypeName = $visitTypeName;
        $this->visitTypeOrder = $visitTypeOrder;
        $this->visitTypeOptional = $visitTypeOptional;
        $this->visitGroupId = $visitGroupId;


    }

    public function setReviewVisitStatus(string $reviewStatus, ?string $reviewConclusionValue, ?string $reviewConclusionDate){
        $this->reviewStatus = $reviewStatus;
        $this->reviewConclusionValue = $reviewConclusionValue;
        $this->reviewConclusionDate = $reviewConclusionDate;
    }

}
