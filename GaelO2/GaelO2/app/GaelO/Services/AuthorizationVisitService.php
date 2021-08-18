<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\VisitRepository;

class AuthorizationVisitService {

    private AuthorizationPatientService $authorizationPatientService;
    private VisitRepository $visitRepository;

    private string $requestedRole;

    protected int $visitId;
    protected array $visitData;
    protected string $studyName;

    public string $visitUploadStatus;

    public function __construct(AuthorizationPatientService $authorizationPatientService, VisitRepository $visitRepository)
    {
        $this->visitRepository = $visitRepository;
        $this->authorizationPatientService = $authorizationPatientService;
    }

    public function setCurrentUserAndRole(int $userId, string $role)
    {
        $this->requestedRole = $role;
        $this->userId = $userId;
        $this->authorizationPatientService->setCurrentUserAndRole($userId, $role);
    }

    public function setVisitId(int $visitId){
        $this->visitId = $visitId;
        $visitContext = $this->visitRepository->getVisitContext($visitId);

        $this->stateQualityControl = $visitContext['state_quality_control'];
        $this->patientStudy = $visitContext['visit_type']['visit_group']['study_name'];
        $this->patientCenter = $visitContext['patient']['center_code'];
        $this->patientCode = $visitContext['patient']['code'];
        $this->visitUploadStatus = $visitContext['upload_status'];
        $this->stateInvestigatorForm = $visitContext['state_investigator_form'];

        $this->authorizationPatientService->setPatientEntity($visitContext['patient']);

    }

    public function isVisitAllowed(): bool {
        //Check that called Role exists for users and visit is not deleted
        if ($this->requestedRole === Constants::ROLE_REVIEWER) {
            //SK ICI DEVRAIT ETRE LIMITE AUX PATIENT QUI ONT UNE VISIT QUI ATTEND UNE REVIEW DANS L EDUTE
            //NECESSITE D INJECTER LE STUDYNAME ICI POUR LES ETUDES ANCILLAIRES
            //Check Role exist and parent patient has one awaiting visit
            return $this->authorizationPatientService->isPatientAllowed() && $this->visitRepository->isParentPatientHavingOneVisitAwaitingReview($this->visitId, $this->patientStudy, $this->userId);
        } else if ($this->requestedRole === Constants::ROLE_CONTROLLER) {
            //For controller controller role should be allows and visit QC status be not done or awaiting definitive conclusion
            $allowedControllerStatus = array(Constants::QUALITY_CONTROL_NOT_DONE, Constants::QUALITY_CONTROL_WAIT_DEFINITIVE_CONCLUSION);
            $allowedInvestigatorFormStatus = array(Constants::INVESTIGATOR_FORM_DONE, Constants::INVESTIGATOR_FORM_NOT_NEEDED);
            if (in_array($this->stateQualityControl, $allowedControllerStatus) && $this->visitUploadStatus === Constants::UPLOAD_STATUS_DONE && in_array($this->stateInvestigatorForm, $allowedInvestigatorFormStatus) ) {
                return $this->authorizationPatientService->isPatientAllowed();
            } else {
                return false;
            }
        } else {
            //Investigator, Supervisor, Monitor simply accept when patient is available in patient's study (no specific rules)
            return $this->authorizationPatientService->isPatientAllowed();
        }

    }

}
