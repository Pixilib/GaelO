<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;

class AuthorizationPatientService {

    private PatientRepositoryInterface $patientRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;
    private int $patientId;
    private array $patientData;

    public function __construct(PatientRepositoryInterface $patientRepositoryInterface, AuthorizationStudyService $authorizationStudyService, AuthorizationUserService $authorizationUserService )
    {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    private function fillPatientData(){
        if( !isset($this->patientData) ) $this->patientData = $this->patientRepositoryInterface->find($this->patientId);
        $this->patientStudy = $this->patientData['study_name'];
        $this->patientCenter = $this->patientData['center_code'];
    }

    public function setPatientId(string $patientId){
        $this->patientId = $patientId;
    }

    public function setUserId(int $userId){
        $this->authorizationStudyService->setUserId($userId);
    }

    public function setStudyName(string $studyName){
        $this->authorizationStudyService->setStudyName($studyName);
    }

    public function setPatientEntity(array $patientEntity){
        $this->patientData = $patientEntity;
    }

    public function getAuthorizationStudyService() : AuthorizationStudyService {
        return $this->authorizationStudyService;
    }

    public function isPatientAllowed(string $requestedRole): bool
    {
        $this->fillPatientData();

        if (  $this->authorizationStudyService->getStudyEntity()->isAncillaryStudy() ) {
            //Reject if requested ancillary study is not ancillary of the orginal patient study
            if( ! $this->authorizationStudyService->getStudyEntity()->isAncillaryStudyOf($this->patientStudy) ) return false;
        }

        if ($requestedRole === Constants::ROLE_INVESTIGATOR ) {
            //Investigator should not access patient outside their centers if show all not set in study
            $showAll = $this->authorizationStudyService->getStudyEntity()->controllerShowAll;
            if ( !$showAll && !$this->authorizationStudyService->getAuthorizationUserService()->isCenterAffiliatedToUser($this->patientCenter) ) return false;
        }

        if ($requestedRole === Constants::ROLE_MONITOR) {
            //Monitor should not access patient outside their centers if show all not set in study
            $showAll = $this->authorizationStudyService->getStudyEntity()->monitorShowAll;
            if ( !$showAll && !$this->authorizationStudyService->getAuthorizationUserService()->isCenterAffiliatedToUser($this->patientCenter) ) return false;
        }


        //For all other cases access granted if role exists in the patient's study
        return $this->authorizationStudyService->isAllowedStudy($requestedRole);


    }

}
