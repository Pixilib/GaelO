<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationPatientService;

class AuthorizationVisitService {

    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationPatientService $authorizationPatientService;
    private int $visitId;
    private array $visitData;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, AuthorizationPatientService $authorizationPatientService)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationPatientService = $authorizationPatientService;
    }

    public function setVisitId(int $visitId){
        $this->visitId = $visitId;
    }

    public function setVisitContext(array $visitContext){
        $this->visitData = $visitContext;
    }

    private function fillVisitData(){
        if($this->visitData == null) $this->visitData = $this->visitRepositoryInterface->getVisitContext($this->visitId);

        $this->stateQualityControl = $this->visitData['state_quality_control'];
        $this->patientStudy = $this->visitData['visit_type']['visit_group']['study_name'];
        $this->patientCenter = $this->visitData['patient']['center_code'];
        $this->patientId = $this->visitData['patient']['id'];
        $this->visitUploadStatus = $this->visitData['upload_status'];
        $this->stateInvestigatorForm = $this->visitData['state_investigator_form'];

    }

    private function isAllowedStatusQC(){
        return in_array($this->stateQualityControl, [Constants::QUALITY_CONTROL_NOT_DONE, Constants::QUALITY_CONTROL_WAIT_DEFINITIVE_CONCLUSION] );
    }

    private function isAllowedInvestigatorFormStatus(){
        return in_array($this->stateInvestigatorForm, [Constants::INVESTIGATOR_FORM_DONE, Constants::INVESTIGATOR_FORM_NOT_NEEDED] );
    }


    public function isVisitAllowed(int $userId, string $requestedRole, string $studyName): bool {

        $this->fillVisitData();

        $this->authorizationPatientService->setPatientEntity($this->visitData['patient']);

        if ($requestedRole === Constants::ROLE_REVIEWER) {
            //Check parent patient allowed and has one awaiting review visit
            return $this->authorizationPatientService->isPatientAllowed($userId, $requestedRole, $studyName) && $this->visitRepositoryInterface->isParentPatientHavingOneVisitAwaitingReview($this->visitId, $this->patientStudy, $this->userId);

        } else if ($requestedRole === Constants::ROLE_CONTROLLER) {
            //For controller visit QC status be not done or awaiting definitive conclusion, Investigator Form should be Done or Not Needed and Upload status should be done
            if ( ! $this->isAllowedStatusQC() || ! $this->isAllowedInvestigatorFormStatus() || $this->visitUploadStatus !== Constants::UPLOAD_STATUS_DONE ) {
                return false;
            }
        }

        //For all other role access depend on patient access
        return $this->authorizationPatientService->isPatientAllowed($userId, $requestedRole, $studyName);

    }




}
