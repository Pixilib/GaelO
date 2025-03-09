<?php

namespace App\GaelO\Services\TreeService;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\PatientEntity;

class ReviewerTreeService extends AbstractTreeService
{

    protected string $role = Constants::ROLE_REVIEWER;

    protected function makePatientDetails(array $patientsEntities): array
    {
        $patientArray = [];
        foreach ($patientsEntities as $patientEntity) {
            $patient = new PatientEntity();
            $patient->metadata = $patientEntity['metadata'];
            $patient->code = $patientEntity['code'];
            $patient->id = $patientEntity['id'];
            $patientArray[$patient->id] = (array) $patient;
        }

        return $patientArray;
    }

    public function buildTree(): array
    {
        //Get patient with at least an awaiting review visit for the current user (visit with review available and review form not validated by user)
        $originalStudy = $this->studyEntity->getOriginalStudyName();
        $ancillaryStudy = null;
        //If requested study is an ancillary study, rename original and ancillary study
        if($this->studyEntity->isAncillaryStudy()){
            $ancillaryStudy = $this->studyEntity->name;
        }
        
        $patientIdsArray = $this->visitRepositoryInterface->getPatientsHavingAtLeastOneAwaitingReviewForUser($originalStudy, $this->userId, $ancillaryStudy);
        $visitArray = $this->visitRepositoryInterface->getReviewVisitHistoryFromPatientIdsWithContextAndReviewStatus($patientIdsArray, $this->studyEntity->name);

        return $this->formatResponse($visitArray);
    }
}
