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

        //Extract the Patient ID Array
        $patientIdArray = array_map(function ($patientEntity) {
            return $patientEntity['id'];
        }, $patientsArray);

        //Get visits of thoose patients
        $patientVisitsArray = $this->visitRepositoryInterface->getPatientListVisitsWithContext($patientIdArray);

        return[
            'patients' => $this->makePatientDetails($patientIdArray),
            'visits'=> $this->makeVisitDetails($patientVisitsArray)
        ];
    }
}
