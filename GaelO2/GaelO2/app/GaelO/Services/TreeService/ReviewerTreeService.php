<?php

namespace App\GaelO\Services\TreeService;

use App\GaelO\Constants\Constants;

class ReviewerTreeService extends AbstractTreeService
{

    protected string $role = Constants::ROLE_REVIEWER;

    public function buildTree(): array
    {

        //Get patient with at least an awaiting review visit for the current user (visit with review available and review form not validated by user)
        $patientIdsArray = $this->visitRepository->getPatientsHavingAtLeastOneAwaitingReviewForUser($this->studyName, $this->userId);
        $patientsArray = $this->patientRepository->getPatientsFromIdArray($patientIdsArray);
        $patientIdCodeArray = [];
        foreach ($patientsArray as $patientEntity) {
            $patientIdCodeArray[$patientEntity['id']] = $patientEntity['code'];
        }

        $patientsIdArray = array_keys($patientIdCodeArray);

        $patientVisitsArray = $this->visitRepository->getPatientListVisitWithContextAndReviewStatus($patientsIdArray, $this->studyName);

        return $this->makeTreeFromVisits($patientVisitsArray);
    }
}
