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

        $resultTree = [];

        foreach ($visitsArray as $visitObject) {

            $patientCode = $visitObject['patient_code'];
            $visitModality =  $visitObject['visit_type']['visit_group']['modality'];
            $visitOrder = $visitObject['visit_type']['order'];

            $resultTree[$patientCode][$visitModality][$visitOrder] =  $this->filterVisitOutputData($visitObject);
        }

        return $resultTree;
    }

    /**
     * Create tree from array of patients, used when all visits of a patient should be listed
     * and not only some specific visits (used for investigators and reviewers)
     */
    private function makeTreeFromPatientsArray(array $patientsCodeArray): array
    {

        $resultTree = [];

        $patientVisitsArray = $this->visitRepository->getPatientListVisitsWithContext($patientsCodeArray);

        foreach ($patientsCodeArray as $patientCode) {
            $resultTree[$patientCode] = [];
        }

        //Add existing visits in sub keys
        foreach ($patientVisitsArray as $visitObject) {
            $visitModality = $visitObject['visit_type']['visit_group']['modality'];
            $visitOrder = $visitObject['visit_type']['order'];
            $patientCode = $visitObject['patient_code'];
            $resultTree[$patientCode][$visitModality][$visitOrder] = $this->filterVisitOutputData($visitObject);
        }

        return $resultTree;
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
            $patientCodeArray = array_map(function ($patientEntity) {
                return $patientEntity['code'];
            }, $patientsArray);
            return $this->makeTreeFromPatientsArray($patientCodeArray);

        } else if ($this->role == Constants::ROLE_CONTROLLER) {

            $visitsArray = $this->visitRepository->getVisitsInStudyAwaitingControllerAction($this->studyName);
            return  $this->makeTreeFromVisits($visitsArray);

        } else if ($this->role == Constants::ROLE_MONITOR) {

            $visitsArray = $this->visitRepository->getVisitsInStudy($this->studyName);
            return  $this->makeTreeFromVisits($visitsArray);

        } else if ($this->role == Constants::ROLE_REVIEWER) {

            //Get patient with at least an awaiting review visit for the current user (visit with review available and review form not validated by user)
            $patientCodeArray = $this->visitRepository->getPatientsHavingAtLeastOneAwaitingReviewForUser($this->studyName, $this->userId);
            return $this->makeTreeFromPatientsArray($patientCodeArray);

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
            'studyName' => $visitEntity['visit_type']['visit_group']['study_name'],
            'stateInvestigatorForm' => $visitEntity['state_investigator_form'],
            'stateQualityControl' => $visitEntity['state_quality_control'],
            'uploadStatus' => $visitEntity['upload_status'],
            'statusDone' => $visitEntity['status_done'],
            'visitTypeId' => $visitEntity['visit_type']['id'],
            'visitGroupId' => $visitEntity['visit_type']['visit_group']['id'],
            'patientCode' => $visitEntity['patient_code']

        ];
    }
}
