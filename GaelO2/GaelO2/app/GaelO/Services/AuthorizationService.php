<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\PatientRepository;
use App\GaelO\Repositories\UserRepository;
use App\GaelO\Repositories\VisitRepository;

class AuthorizationService {

    private int $userId;
    private bool $administrator;
    private array $userData;


    public function __construct(UserRepository $userRepository, PatientRepository $patientRepository, VisitService $visitService) {
        $this->userRepository = $userRepository;
        $this->patientRepository = $patientRepository;
        $this->visitService = $visitService;
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

    /**
     * Return if at least one of an array roles is existing for user
     */
    public function isOnOfRolesAllowed(array $roles, string $studyName){
        $existingRoles = $this->userRepository->getUsersRolesInStudy($this->userId, $studyName);
        return sizeof(array_intersect($roles, $existingRoles)) > 0 ;
    }

    public function isPatientAllowed(int $patientCode, string $role) : bool {

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

    public function isVisitAllowed(int $visitId, string $role) : bool {

        $visitData  = $this->visitService->getVisitData($visitId);
        $patientCode = $visitData['patient_code'];

        $visitEntity = $this->visitService->getVisitContext($visitId);
        $studyName = $visitEntity['visit_group']['study_name'];

		//Check that called Role exists for users and visit is not deleted
        if ($role === Constants::ROLE_INVESTIGATOR) {
            return $this->isPatientAllowed($patientCode, $role);
        }else if ($role === Constants::ROLE_REVIEWER) {
            //For reviewer the visit access is allowed if one of the created visits is still awaiting review
            //This is made to allow access to references scans
            //SK ICI A FAIRE
            //$patientObject=$visitData->getPatient();
            //$isAwaitingReview=$patientObject->getPatientStudy()->isHavingAwaitingReviewImagingVisit();
            return $this->isRoleAllowed($role, $studyName);
        } else if ($role === Constants::ROLE_CONTROLER) {
            //For controller controller role should be allows and visit QC status be not done or awaiting definitive conclusion
            $allowedStatus = array(Constants::QUALITY_CONTROL_NOT_DONE, Constants::QUALITY_CONTROL_WAIT_DEFINITIVE_CONCLUSION);
            if( $this->isRoleAllowed($role, $studyName) && in_array($visitData['state_quality_control'], $allowedStatus) ){
                return true;
            } else {
                return false;
            }
        } else {
            //Supervisor, Admin, Monitor simply accept when role is available in patient's study (no specific rules)
            return $this->isRoleAllowed($role, $studyName);
        }

        return false;
    }

}
