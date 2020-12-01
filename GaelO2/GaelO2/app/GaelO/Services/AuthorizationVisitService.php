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
    public bool $visitReviewAvailable;

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

    public function setVisitId($visitId){
        $this->visitId = $visitId;
        $visitContext = $this->visitRepository->getVisitContext($visitId);

        $this->stateQualityControl = $visitContext['state_quality_control'];
        $this->patientStudy = $visitContext['visit_type']['visit_group']['study_name'];
        $this->patientCenter = $visitContext['patient']['center_code'];
        $this->patientCode = $visitContext['patient']['code'];


        $this->visitUploadStatus = $visitContext['upload_status'];

        $this->authorizationPatientService->setPatientEntity($visitContext['patient']);
        //$this->visitReviewAvailable = $visitContext['review_available'];
        //SK ICI PROBLEME IL FAUT FAIRE VENIR LA STUDY DEMANDEE DEPUIS LE FRONT VU
        // QUN VISIT ID VA ETRE ASSOCIEE A PLUSIEURS ETUDE ANCILLAIRE
        //DANS LE CHECK PATIENT IL FAUT CHECK QUE LA STUDY APPELEE SOIT UNE ETUDE ANCILLAIRE
        //DE L ETUDE PRINCEPS AUQUEL EST ATTACHE LE PATIENT
        $this->visitReviewAvailable = true;

    }

    public function isVisitAllowed(): bool {
        //Check that called Role exists for users and visit is not deleted
        if ($this->requestedRole === Constants::ROLE_REVIEWER) {
            $this->visitRepository->isVisitAvailableForReview($this->visitId, $this->studyName, $this->userId);

            return $this->authorizationPatientService->isPatientAllowed();
        } else if ($this->requestedRole === Constants::ROLE_CONTROLER) {
            //For controller controller role should be allows and visit QC status be not done or awaiting definitive conclusion
            $allowedControllerStatus = array(Constants::QUALITY_CONTROL_NOT_DONE, Constants::QUALITY_CONTROL_WAIT_DEFINITIVE_CONCLUSION);
            if (in_array($this->stateQualityControl, $allowedControllerStatus)) {
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
