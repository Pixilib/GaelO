<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\PatientRepository;
use App\GaelO\Repositories\UserRepository;

class AuthorizationService {

    private int $userId;
    private bool $administrator;
    private array $userData;


    public function __construct(UserRepository $userRepository, PatientRepository $patientRepository) {
        $this->userRepository = $userRepository;
        $this->patientRepository = $patientRepository;
    }

    public function setCurrentUser(int $userId ){
        $this->userId = $userId;
        $this->userData = $this->userRepository->find($userId);
        $this->administrator = $this->userData['administrator'];
    }

    public function isAdmin() : bool {
        return $this->administrator;
    }

    public function isRoleAllowed(string $role, string $studyName){
        $existingRoles = $this->userRepository->getUsersRolesInStudy($this->userId, $studyName);
        return in_array($role, $existingRoles);
    }

    public function isPatientAllowed(string $role, int $patientCode) : bool {

        $patientDetails = $this->patientRepository->find($patientCode);
        $patientStudy = $patientDetails['study_name'];
        $patientCenter = $patientDetails['center_code'];

        if($role === Constants::ROLE_INVESTIGATOR){
            //For Investigator check that asked patient is in user's centers
            //And user having investigator permission in patient's study
            $usersCenters = $this->userRepository->getAllUsersCenters($this->userId);
            if( in_array($patientCenter, $usersCenters)
                && $this->isRoleAllowed($role, $patientStudy) ) return true;

        }else{
            //For all other rols
            //Check user has investigator permission in patient's study
            return $this->isRoleAllowed($role, $patientStudy);
        }

        return false;
    }

    public function isVisitAllowed(int $visitId){

    }

}
