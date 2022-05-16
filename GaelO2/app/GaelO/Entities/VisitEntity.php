<?php

namespace App\GaelO\Entities;

use App\GaelO\Constants\Constants;

class VisitEntity {
    public ?int $creatorUserId;
    public ?string $creationDate;
    public string $patientId;
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
    public ?string $deletedAt;

    public VisitGroupEntity $visitGroup;
    public VisitTypeEntity $visitType;
    public PatientEntity $patient;
    public UserEntity $creatorUser;

    public ?string $reviewStatus;
    public ?string $reviewConclusionValue;
    public ?string $reviewConclusionDate;
    public ?array $targetLesions;



    public static function fillFromDBReponseArray(array $array){
        $visitEntity  = new VisitEntity();
        $visitEntity->id = $array['id'];
        $visitEntity->creatorUserId = $array['creator_user_id'];
        $visitEntity->creationDate = $array['creation_date'];
        $visitEntity->patientId = $array['patient_id'];
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
        $visitEntity->correctiveActionDecision = $array['corrective_action_applied'];
        $visitEntity->lastReminderUpload = $array['last_reminder_upload'];
        $visitEntity->deletedAt = $array['deleted_at'];
        return $visitEntity;
    }

    public function fillForTree($visitArray){
        $this->id = $visitArray['id'];
        $this->patientId = $visitArray['patient_id'];
        $this->visitTypeId = $visitArray['visit_type_id'];
        $this->statusDone = $visitArray['status_done'];
        $this->visitDate = $visitArray['visit_date'];
        $this->uploadStatus = $visitArray['upload_status'];
        $this->stateInvestigatorForm = $visitArray['state_investigator_form'];
        $this->stateQualityControl = $visitArray['state_quality_control'];
    }

    public function setVisitContext(array $visitGroupEntity ,array $visitTypeEntity){
        $this->visitGroup = VisitGroupEntity::fillFromDBReponseArray($visitGroupEntity);
        $this->visitType = VisitTypeEntity::fillFromDBReponseArray($visitTypeEntity);
    }

    public function setPatientEntity(array $patientEntity){
        $this->patient = PatientEntity::fillFromDBReponseArray($patientEntity);
    }

    public function setReviewVisitStatus(?string $reviewStatus, ?string $reviewConclusionValue, ?string $reviewConclusionDate, ?array $targetLesions){
        $this->reviewStatus = $reviewStatus === null ? Constants::REVIEW_STATUS_NOT_DONE : $reviewStatus;
        $this->reviewConclusionValue = $reviewConclusionValue;
        $this->reviewConclusionDate = $reviewConclusionDate;
        $this->targetLesions  = $targetLesions;
    }

    public function setCreatorDetails(string $firstname, string $lastname) : void {
        $userEntity = new UserEntity();
        $userEntity->firstname = $firstname;
        $userEntity->lastname = $lastname;
        $this->creatorUser = $userEntity;
    }
}
