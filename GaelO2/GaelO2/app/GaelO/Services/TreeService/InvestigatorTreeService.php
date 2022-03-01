<?php

namespace App\GaelO\Services\TreeService;

use App\GaelO\Constants\Constants;

class InvestigatorTreeService extends AbstractTreeService
{

    protected string $role = Constants::ROLE_INVESTIGATOR;


    public function buildTree(): array
    {
        //retrieve from DB the patient's list of the requested study and included in user's center or affiliated centers
        $userCentersArray = $this->userRepositoryInterface->getAllUsersCenters($this->userId);
        $patientsArray = $this->patientRepositoryInterface->getPatientsInStudyInCenters($this->studyEntity->name, $userCentersArray);

        $patientIdArray = [];
        foreach ($patientsArray as $patientEntity) {
            $patientIdArray[$patientEntity['id']] = $patientEntity['code'];
        }

        $patientsIdArray = array_keys($patientIdArray);
        $patientVisitsArray = $this->visitRepositoryInterface->getPatientListVisitsWithContext($patientsIdArray);

        foreach ($patientIdArray as $id=>$code) {
            $responseArray['patients'][$id] = $code;
        }


        return [...$this->makeTreeFromVisits($patientVisitsArray), ...$responseArray];
    }
}
