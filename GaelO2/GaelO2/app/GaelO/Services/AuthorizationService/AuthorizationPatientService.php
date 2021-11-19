<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;

class AuthorizationPatientService {

    private PatientRepositoryInterface $patientRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;
    private AuthorizationUserService $authorizationUserService;
    private int $patientId;
    private array $patientData;

    public function __construct(PatientRepositoryInterface $patientRepositoryInterface, AuthorizationStudyService $authorizationStudyService, AuthorizationUserService $authorizationUserService )
    {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
        $this->authorizationUserService = $authorizationUserService;
    }

    private function fillPatientData(){
        if( !isset($this->patientData) ) $this->patientData = $this->patientRepositoryInterface->find($this->patientId);
        $this->patientStudy = $this->patientData['study_name'];
        $this->patientCenter = $this->patientData['center_code'];
    }

    public function setPatientId(string $patientId){
        $this->patientId = $patientId;
    }

    public function setPatientEntity(array $patientEntity){
        $this->patientData = $patientEntity;
    }

    public function isPatientAllowed(int $userId, string $requestedRole, string $studyName): bool
    {
        $this->fillPatientData();

        $this->authorizationStudyService->setStudyName($studyName);
        $this->authorizationUserService->setUserId($userId);

        if (  $this->authorizationStudyService->isAncillaryStudy() ) {
            //Reject if requested ancillary study is not ancillary of the orginal patient study
            if( ! $this->authorizationStudyService->isAncillaryStudyOf($this->patientStudy) ) return false;
        }

        if ($requestedRole === Constants::ROLE_INVESTIGATOR) {
            //Investigator should not access patient outside their centers
            if (  !$this->authorizationUserService->isCenterAffiliatedToUser($this->patientCenter) ) return false;
        }

        //For all other cases access granted if role exists in the patient's study
        return $this->authorizationStudyService->isAllowedStudy($userId, $requestedRole, $studyName);


    }

}
