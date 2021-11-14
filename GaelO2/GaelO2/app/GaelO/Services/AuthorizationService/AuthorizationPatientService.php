<?php

namespace App\GaelO\Services\AuthorizationService;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;

class AuthorizationPatientService {

    private PatientRepositoryInterface $patientRepositoryInterface;
    private string $requestedRole;
    private int $patientId;
    private array $patientData;

    public function __construct(PatientRepositoryInterface $patientRepositoryInterface)
    {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
    }

    private function fillPatientData(){
        if($this->patientData == null) $this->patientData = $this->patientRepositoryInterface->find($this->patientId);
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
        $authorizationStudyService = FrameworkAdapter::make(AuthorizationStudyService::class);
        $authorizationStudyService->setStudyName($studyName);

        $authorizationUserService = FrameworkAdapter::make(AuthorizationUserService::class);
        $authorizationUserService->setUserId($userId);

        if (  $authorizationStudyService->isAncillaryStudy() ) {
            //Reject if requested ancillary study is not ancillary of the orginal patient study
            if( ! $authorizationStudyService->isAncillaryStudyOf($this->patientStudy) ) return false;
        }

        if ($requestedRole === Constants::ROLE_INVESTIGATOR) {
            //Investigator should not access patient outside their centers
            if (  !$authorizationUserService->isCenterAffiliatedToUser($this->patientCenter) ) return false;
        }

        //For all other cases access granted if role exists in the patient's study
        return $this->authorizationStudyService->isAllowedStudy($userId, $requestedRole, $studyName);


    }

}
