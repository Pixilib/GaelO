<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Repositories\PatientRepository;
use App\GaelO\Repositories\UserRepository;
use App\GaelO\Repositories\VisitRepository;

/**
 * Build JSON for JSTree with patient's / visit's data
 *
 */

class VisitTreeService
{
    private $role;
    private $userId;
    private $studyName;
    private PatientRepository $patientRepository;
    private VisitRepository $visitRepository;
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository, PatientRepository $patientRepository, VisitRepository $visitRepository)
    {
        $this->patientRepository = $patientRepository;
        $this->userRepository = $userRepository;
        $this->visitRepository = $visitRepository;
    }

    public function setUserAndStudy(int $userId, string $role, string $studyName)
    {
        $this->userId = $userId;
        $this->role = $role;
        $this->studyName = $studyName;
    }

    private function makeTreeFromVisits(array $visitsArray): array
    {

        $responseArray = [];
        $responseArray['visits'] = [];
        $responseArray['patients'] = [];

        $patientIdsArray = array_unique(array_map(function ($visit) {
            return $visit['patient_id'];
        }, $visitsArray));

        $patientsArray = $this->patientRepository->getPatientsFromIdArray($patientIdsArray);
        foreach($patientsArray as $patientEntity) {
            $responseArray['patients'][$patientEntity['id']] = $patientEntity['code'];
        }

        foreach ($visitsArray as $visitObject) {
            $visitsFormattedData = $this->filterVisitOutputData($visitObject);
            $responseArray['visits'][] = $visitsFormattedData;
        }

        return $responseArray;
    }

    /**
     * Create tree from array of patients, used when all visits of a patient should be listed
     * and not only some specific visits (used for investigators and reviewers)
     */
    private function makeTreeFromPatientsArray(array $patientsIdCodeArray): array
    {

        $patientsIdArray = array_keys($patientsIdCodeArray);
        $patientVisitsArray = [];

        //If Reviewer need to add review status for tree selections
        if ($this->role === Constants::ROLE_REVIEWER) $patientVisitsArray = $this->visitRepository->getPatientListVisitWithContextAndReviewStatus($patientsIdArray, $this->studyName);
        else $patientVisitsArray = $this->visitRepository->getPatientListVisitsWithContext($patientsIdArray);

        $responseArray = [];
        $responseArray['visits'] = [];
        $responseArray['patients'] = [];
        //format visits data
        foreach ($patientVisitsArray as $visitObject) {
            $responseArray['visits'][] = $this->filterVisitOutputData($visitObject);
        }

        foreach ($patientsIdCodeArray as $id=>$code) {
            $responseArray['patients'][$id] = $code;
        }

        return $responseArray;
    }

    /**
     * Return JSON for JSTree according to role  (patient + Visit)
     * @return array
     */
    public function buildTree()
    {

        if ($this->role == Constants::ROLE_INVESTIGATOR) {

            //retrieve from DB the patient's list of the requested study and included in user's center or affiliated centers
            $userCentersArray = $this->userRepository->getAllUsersCenters($this->userId);
            $patientsArray = $this->patientRepository->getPatientsInStudyInCenters($this->studyName, $userCentersArray);
            $patientIdArray = [];
            foreach($patientsArray as $patientEntity) {
                $patientIdArray[$patientEntity['id']] = $patientEntity['code'];
            }
            return $this->makeTreeFromPatientsArray($patientIdArray);

        } else if ($this->role == Constants::ROLE_CONTROLLER) {

            $visitsArray = $this->visitRepository->getVisitsInStudyAwaitingControllerAction($this->studyName);
            return  $this->makeTreeFromVisits($visitsArray);

        } else if ($this->role == Constants::ROLE_MONITOR) {

            $visitsArray = $this->visitRepository->getVisitsInStudy($this->studyName, false, false);
            return  $this->makeTreeFromVisits($visitsArray);

        } else if ($this->role == Constants::ROLE_REVIEWER) {

            //Get patient with at least an awaiting review visit for the current user (visit with review available and review form not validated by user)
            $patientIdsArray = $this->visitRepository->getPatientsHavingAtLeastOneAwaitingReviewForUser($this->studyName, $this->userId);
            $patientsArray = $this->patientRepository->getPatientsFromIdArray($patientIdsArray);
            $patientIdCodeArray = [];
            foreach($patientsArray as $patientEntity) {
                $patientIdCodeArray[$patientEntity['id']] = $patientEntity['code'];
            }
            return $this->makeTreeFromPatientsArray($patientIdCodeArray);

        } else {
            throw new GaelOBadRequestException('Not Authorized role for tree generation');
        }
    }

    /**
     * Return valuable data to be displayed to frondend
     */
    private function filterVisitOutputData(array $visitEntity): array
    {
        return [
            'id' => $visitEntity['id'],
            'name' => $visitEntity['visit_type']['name'],
            'order' => $visitEntity['visit_type']['order'],
            'optional' => $visitEntity['visit_type']['optional'],
            'modality' => $visitEntity['visit_type']['visit_group']['modality'],
            'modalityName' => $visitEntity['visit_type']['visit_group']['name'],
            'studyName' => $visitEntity['visit_type']['visit_group']['study_name'],
            'stateInvestigatorForm' => $visitEntity['state_investigator_form'],
            'stateQualityControl' => $visitEntity['state_quality_control'],
            'uploadStatus' => $visitEntity['upload_status'],
            'statusDone' => $visitEntity['status_done'],
            'visitTypeId' => $visitEntity['visit_type']['id'],
            'visitGroupId' => $visitEntity['visit_type']['visit_group']['id'],
            'patientId' => $visitEntity['patient_id'],
            'reviewStatus' => array_key_exists('review_status', $visitEntity) ? $visitEntity['review_status']['review_status'] : null
        ];
    }
}
