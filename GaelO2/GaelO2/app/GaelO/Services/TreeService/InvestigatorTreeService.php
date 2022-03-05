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

        $responseArray = $this->makeTreeFromVisits($patientVisitsArray);

        //SK A REFACTORISER DEUX APPELS A GETCENTERFROMCODEARRAY
        $centers = $this->centerRepositoryInterface->getCentersFromCodeArray($userCentersArray);
        //reloop to add patient with no visits
        foreach ($patientsArray as $patientEntity) {
            $centerIndex = array_search($patientEntity['center_code'], array_column($centers, 'code'));
            $responseArray['patients'][$patientEntity['id']] = [
                'code' => $patientEntity['code'],
                'centerName' => $centers[$centerIndex]['name'],
                'centerCode' => $patientEntity['center_code']
            ];
        }


        return $responseArray;
    }
}
