<?php

namespace App\GaelO\Services\TreeService;

use App\GaelO\Constants\Constants;

class ReviewerTreeService extends AbstractTreeService
{

    protected string $role = Constants::ROLE_REVIEWER;

    public function buildTree(): array
    {

        //Get patient with at least an awaiting review visit for the current user (visit with review available and review form not validated by user)
        $patientIdsArray = $this->visitRepositoryInterface->getPatientsHavingAtLeastOneAwaitingReviewForUser($this->studyEntity->name, $this->userId);

        $patientsArray = $this->patientRepositoryInterface->getPatientsFromIdArray($patientIdsArray);
        $patientIdCodeArray = [];
        foreach ($patientsArray as $patientEntity) {
            $patientIdCodeArray[$patientEntity['id']] = $patientEntity['code'];
        }

        $patientsIdArray = array_keys($patientIdCodeArray);
        $patientVisitsArray = $this->visitRepositoryInterface->getPatientListVisitWithContextAndReviewStatus($patientsIdArray, $this->studyEntity->name);

        return $this->makeTreeFromVisits($patientVisitsArray);
    }
}
