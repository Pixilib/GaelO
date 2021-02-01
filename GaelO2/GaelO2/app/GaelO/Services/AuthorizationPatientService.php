<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\PatientRepository;
use Illuminate\Support\Facades\Log;

class AuthorizationPatientService {

    private AuthorizationService $authorizationService;
    private PatientRepository $patientRepository;

    private string $patientStudy;
    private int $patientCenter;

    public function __construct(AuthorizationService $authorizationService, PatientRepository $patientRepository)

    {
        $this->authorizationService = $authorizationService;
        $this->patientRepository = $patientRepository;

    }

    public function setCurrentUserAndRole(int $userId, string $role)
    {
        $this->authorizationService->setCurrentUserAndRole($userId, $role);
    }

    public function setPatient(int $patientCode){
        $patientDetails = $this->patientRepository->find($patientCode);
        $this->patientStudy = $patientDetails['study_name'];
        $this->patientCenter = $patientDetails['center_code'];
    }

    public function setPatientEntity(array $patientEntity){
        $this->patientStudy = $patientEntity['study_name'];
        $this->patientCenter = $patientEntity['center_code'];
    }

    public function isPatientAllowed(): bool
    {

        if ($this->authorizationService->requestedRole === Constants::ROLE_INVESTIGATOR) {

            //For Investigator check that asked patient is in user's centers
            //And user having investigator permission in patient's study
            if ( $this->authorizationService->isCenterAffiliatedToUser($this->patientCenter) ) {
                return $this->authorizationService->isRoleAllowed($this->patientStudy);
            }else {
                return false;
            }

        } else {
            //For all other roles
            //Check user has investigator permission in patient's study
            return $this->authorizationService->isRoleAllowed($this->patientStudy);
        }

    }

}
