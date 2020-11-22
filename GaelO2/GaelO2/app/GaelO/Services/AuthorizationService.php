<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\OrthancSeriesRepository;
use App\GaelO\Repositories\OrthancStudyRepository;
use App\GaelO\Repositories\PatientRepository;
use App\GaelO\Repositories\UserRepository;

class AuthorizationService
{

    private int $userId;
    private bool $administrator;
    private array $userData;


    public function __construct(
        UserRepository $userRepository,
        PatientRepository $patientRepository,
        OrthancStudyRepository $orthancStudyRepository,
        OrthancSeriesRepository $orthancSeriesRepository,
        VisitService $visitService
    ) {
        $this->userRepository = $userRepository;
        $this->patientRepository = $patientRepository;
        $this->visitService = $visitService;
        $this->orthancStudyRepository = $orthancStudyRepository;
        $this->orthancSeriesRepository = $orthancSeriesRepository;
    }

    public function setCurrentUser(int $userId)
    {
        $this->userId = $userId;
        $this->userData = $this->userRepository->find($userId);
        $this->administrator = $this->userData['administrator'];
    }

    public function isAdmin(): bool
    {
        return $this->administrator;
    }

    public function isRoleAllowed(string $role, string $studyName)
    {
        $existingRoles = $this->userRepository->getUsersRolesInStudy($this->userId, $studyName);
        return in_array($role, $existingRoles);
    }

    /**
     * Return if at least one of an array roles is existing for user
     */
    public function isOnOfRolesAllowed(array $roles, string $studyName)
    {
        $existingRoles = $this->userRepository->getUsersRolesInStudy($this->userId, $studyName);
        return sizeof(array_intersect($roles, $existingRoles)) > 0;
    }

    public function isPatientAllowed(int $patientCode, string $role): bool
    {

        $patientDetails = $this->patientRepository->find($patientCode);
        $patientStudy = $patientDetails['study_name'];
        $patientCenter = $patientDetails['center_code'];

        if ($role === Constants::ROLE_INVESTIGATOR) {
            //For Investigator check that asked patient is in user's centers
            //And user having investigator permission in patient's study
            $usersCenters = $this->userRepository->getAllUsersCenters($this->userId);
            if (
                in_array($patientCenter, $usersCenters)
                && $this->isRoleAllowed($role, $patientStudy)
            ) return true;
        } else {
            //For all other rols
            //Check user has investigator permission in patient's study
            return $this->isRoleAllowed($role, $patientStudy);
        }

        return false;
    }

    public function isVisitAllowed(int $visitId, string $role): bool
    {

        $visitData  = $this->visitService->getVisitData($visitId);
        $patientCode = $visitData['patient_code'];

        $visitEntity = $this->visitService->getVisitContext($visitId);
        $studyName = $visitEntity['visit_group']['study_name'];

        //Check that called Role exists for users and visit is not deleted
        if ($role === Constants::ROLE_INVESTIGATOR) {
            return $this->isPatientAllowed($patientCode, $role);
        } else if ($role === Constants::ROLE_REVIEWER) {
            //For reviewer the visit access is allowed if one of the created visits is still awaiting review
            //This is made to allow access to references scans
            //SK ICI A FAIRE
            //$patientObject=$visitData->getPatient();
            //$isAwaitingReview=$patientObject->getPatientStudy()->isHavingAwaitingReviewImagingVisit();
            return $this->isRoleAllowed($role, $studyName);
        } else if ($role === Constants::ROLE_CONTROLER) {
            //For controller controller role should be allows and visit QC status be not done or awaiting definitive conclusion
            $allowedStatus = array(Constants::QUALITY_CONTROL_NOT_DONE, Constants::QUALITY_CONTROL_WAIT_DEFINITIVE_CONCLUSION);
            if ($this->isRoleAllowed($role, $studyName) && in_array($visitData['state_quality_control'], $allowedStatus)) {
                return true;
            } else {
                return false;
            }
        } else {
            //Supervisor, Monitor simply accept when role is available in patient's study (no specific rules)
            return $this->isRoleAllowed($role, $studyName);
        }

        return false;
    }










    public function isDicomWebAccessGranted(string $requestedURI, string $role): bool
    {

        if ($this->endsWith($requestedURI, "/series"))  $level = "studies";
        else $level = "series";

        $includedDeleted = $role === Constants::ROLE_SUPERVISOR ? true : false;

        //Extract StudyInstanceUID from requested URI
        $requestedInstanceUID = $this->getUID($requestedURI, $level);

        if ($level === "series") {
            $seriesEntity = $this->orthancSeriesRepository->getStudyBySeriesInstanceUID($requestedInstanceUID, $includedDeleted);
            $visitEntity = $this->orthancStudyRepository->getParentVisit($seriesEntity['orthanc_study_id']);
        } else if ($level === "studies") {
            $studyEntity = $this->orthancStudyRepository->getStudyByStudyInstanceUID($requestedInstanceUID, $includedDeleted);
            $visitEntity = $this->orthancStudyRepository->getParentVisit($studyEntity['orthanc_id']);
        }

        //Return test of acess allowance
        return $this->isDicomAccessAllowedForUser($visitEntity['id'], $role);
    }



    /**
     * Check that visit is granter for the calling user (still awaiting review or still awaiting QC)
     * @param string $id_visit
     * @return boolean
     */
    public function isDicomAccessAllowedForUser(int $visitId, string $role): bool
    {

        //SK ICI DOUBLE APPEL A LA DB CAR RE APPELE DANS VISIT ALLOWED PEUT ETRE A ENCAPSULER LE TOUT
        //OU VIA HERITAGE de AUTHORIZATIOn
        $visitData  = $this->visitService->getVisitData($visitId);
        $uploadStatus = $visitData['upload_status'];

        //Check Visit Availability of the calling user
        if ($role == Constants::ROLE_REVIEWER || ($role == Constants::ROLE_INVESTIGATOR && $uploadStatus == Constants::UPLOAD_STATUS_DONE)) {
            //Check that visit is in patient that is still awaiting for some reviews
            $visitCheck = $this->isVisitAllowed($visitId, $role);
        } else if ($role == Constants::ROLE_CONTROLER) {
            $visitCheck = $this->isVisitAllowed($visitId, $role);
        } else if ($role == Constants::ROLE_SUPERVISOR) {
            $visitCheck = $this->isVisitAllowed($visitId, $role);
        } else {
            //Other roles (monitor) can't have access to images
            $visitCheck = false;
        }

        return $visitCheck;
    }

    /**
     * Isolate the called Study or Series Instance UID
     * @return string
     */
    private function getUID(string $requestedURI, string $level): string
    {
        $studySubString = strstr($requestedURI, "/" . $level . "/");
        $studySubString = str_replace("/" . $level . "/", "", $studySubString);
        $endStudyUIDPosition = strpos($studySubString, "/");
        $studyUID = substr($studySubString, 0, $endStudyUIDPosition);
        return $studyUID;
    }

    private function endsWith(string $haystack, string $needle): bool
    {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }
}
