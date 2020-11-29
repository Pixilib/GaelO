<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
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


    /**
     * Build array of visits of all child visits in patient array
     */
    private function getVisitsArrayFromPatientsArray($patientsArray) : array
    {

        $visitsArray = [];
        foreach ($patientsArray as $patient) {
            array_push($visitsArray, ...$this->visitRepository->getPatientVisitsWithContext($patient['code']) );
        }

        return $visitsArray;
    }

    private function makeTreeFromVisits(array $visitsArray) : array
    {

        $resultTree = [];

        foreach ($visitsArray as $visitObject) {

            $patientCode = $visitObject['patient_code'];
            //$visitType = $visitObject['name'];
            $visitModality =  $visitObject['modality'];
            $visitOrder = $visitObject['visit_order'];

            //SK ICI FILTER LES INFORMATION A FAIRE PASSER AU FRONT
            $resultTree[ $patientCode ] [ $visitModality ] [$visitOrder] = $visitObject;
        }

        return $resultTree;
    }

    /**
     * Return JSON for JSTree according to role  (patient + Visit)
     * @return array
     */
    public function buildTree()
    {
        $visitsArray = [];

        if ($this->role == Constants::ROLE_INVESTIGATOR) {
            //retrieve from DB the patient's list of the requested study and included in user's center or affiliated centers
            $userCentersArray = $this->userRepository->getAllUsersCenters($this->userId);
            $patientsArray = $this->patientRepository->getPatientsInStudyInCenters($this->studyName, $userCentersArray);
            $visitsArray = $this->getVisitsArrayFromPatientsArray($patientsArray);

        } else if ($this->role == Constants::ROLE_CONTROLER) {

            $visitsArray = $this->visitRepository->getVisitsInStudyAwaitingControllerAction($this->studyName);

        } else if ($this->role == Constants::ROLE_MONITOR) {
            $visitsArray = $this->visitRepository->getVisitsInStudy($this->studyName);

        } else if ($this->role == Constants::ROLE_REVIEWER) {
            //SK A FAIRE recupérer la liste des visites pour lesquelles on attend un review
            //de l'utilisateur courrant
        }


        return  $this->makeTreeFromVisits($visitsArray);
    }
}
