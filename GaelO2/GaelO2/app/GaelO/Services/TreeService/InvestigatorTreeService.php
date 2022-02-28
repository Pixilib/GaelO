<?php

namespace App\GaelO\Services\TreeService;

use App\GaelO\Constants\Constants;

class InvestigatorTreeService extends AbstractTreeService
{

    protected string $role = Constants::ROLE_INVESTIGATOR;


    public function buildTree(): array
    {
        //retrieve from DB the patient's list of the requested study and included in user's center or affiliated centers
        $userCentersArray = $this->userRepository->getAllUsersCenters($this->userId);
        $patientsArray = $this->patientRepository->getPatientsInStudyInCenters($this->studyName, $userCentersArray);

        $patientIdArray = [];
        foreach ($patientsArray as $patientEntity) {
            $patientIdArray[$patientEntity['id']] = $patientEntity['code'];
        }

        $patientsIdArray = array_keys($patientIdArray);
        $patientVisitsArray = $this->visitRepository->getPatientListVisitsWithContext($patientsIdArray);


        return $this->makeTreeFromVisits($patientVisitsArray);
    }
}
