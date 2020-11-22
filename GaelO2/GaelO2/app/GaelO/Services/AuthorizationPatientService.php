<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;

class AuthorizationPatientService extends AuthorizationService{

    protected string $patientCode;
    protected string $patientStudy;
    protected int $patientCenter;

    public function setPatient(int $patientCode){
        $this->patientCode = $patientCode;
        $patientDetails = $this->patientRepository->find($this->patientCode);
        $this->patientStudy = $patientDetails['study_name'];
        $this->patientCenter = $patientDetails['center_code'];
    }

    public function isPatientAllowed(): bool
    {


        if ($this->role === Constants::ROLE_INVESTIGATOR) {
            //For Investigator check that asked patient is in user's centers
            //And user having investigator permission in patient's study
            $usersCenters = $this->userRepository->getAllUsersCenters($this->userId);
            if (
                in_array($this->patientCenter, $usersCenters)
                && $this->isRoleAllowed($this->patientStudy)
            ) return true;
        } else {
            //For all other rols
            //Check user has investigator permission in patient's study
            return $this->isRoleAllowed($this->patientStudy);
        }

        return false;
    }

}
