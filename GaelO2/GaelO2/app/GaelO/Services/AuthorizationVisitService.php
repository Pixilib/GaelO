<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;

class AuthorizationVisitService extends AuthorizationPatientService{

    protected int $visitId;
    protected array $visitData;
    protected string $studyName;

    protected string $visitUploadStatus;
    protected bool $visitReviewAvailable;

    public function setVisitId($visitId){
        $this->visitId = $visitId;
        $visitContext = $this->visitService->getVisitContext($visitId);

        $this->stateQualityControl = $visitContext['state_quality_control'];
        $this->patientStudy = $visitContext['visit_type']['visit_group']['study_name'];
        $this->patientCenter = $visitContext['patient']['center_code'];
        $this->patientCode = $visitContext['patient']['code'];


        $this->visitUploadStatus = $visitContext['upload_status'];
        //$this->visitReviewAvailable = $visitContext['review_available'];
        //SK ICI PROBLEME IL FAUT FAIRE VENIR LA STUDY DEMANDEE DEPUIS LE FRONT VU
        // QUN VISIT ID VA ETRE ASSOCIEE A PLUSIEURS ETUDE ANCILLAIRE
        //DANS LE CHECK PATIENT IL FAUT CHECK QUE LA STUDY APPELEE SOIT UNE ETUDE ANCILLAIRE
        //DE L ETUDE PRINCEPS AUQUEL EST ATTACHE LE PATIENT
        $this->visitReviewAvailable = true;

    }

    public function isVisitAllowed(): bool
    {
        //Check that called Role exists for users and visit is not deleted
        if ($this->role === Constants::ROLE_INVESTIGATOR) {
            return $this->isPatientAllowed();
        } else if ($this->role === Constants::ROLE_REVIEWER) {
            //For reviewer the visit access is allowed if one of the created visits is still awaiting review
            //This is made to allow access to references scans
            //SK ICI A FAIRE
            //$patientObject=$visitData->getPatient();
            //$isAwaitingReview=$patientObject->getPatientStudy()->isHavingAwaitingReviewImagingVisit();
            return $this->isPatientAllowed();
        } else if ($this->role === Constants::ROLE_CONTROLER) {
            //For controller controller role should be allows and visit QC status be not done or awaiting definitive conclusion
            $allowedControllerStatus = array(Constants::QUALITY_CONTROL_NOT_DONE, Constants::QUALITY_CONTROL_WAIT_DEFINITIVE_CONCLUSION);
            if (in_array($this->stateQualityControl, $allowedControllerStatus)) {
                return $this->isPatientAllowed();
            } else {
                return false;
            }
        } else {
            //Supervisor, Monitor simply accept when role is available in patient's study (no specific rules)
            return $this->isRoleAllowed($this->studyName);
        }

        return false;
    }

}
