<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\VisitTypeEntity;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitTypeRepositoryInterface;

class CreateVisitService
{

    private VisitTypeRepositoryInterface $visitTypeRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;

    public function __construct(VisitTypeRepositoryInterface $visitTypeRepositoryInterface, VisitRepositoryInterface $visitRepositoryInterface)
    {
        $this->visitTypeRepositoryInterface = $visitTypeRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function createVisit(
        string $studyName,
        int $creatorUserId,
        string $patientId,
        ?string $visitDate,
        int $visitTypeId,
        string $statusDone,
        ?string $reasonForNotDone
    ): int {

        $visitTypeData = $this->visitTypeRepositoryInterface->find($visitTypeId);
        $visitTypeEntity = VisitTypeEntity::fillFromDBReponseArray($visitTypeData);

        $stateInvestigatorForm = Constants::INVESTIGATOR_FORM_NOT_DONE;
        $stateQualityControl = Constants::QUALITY_CONTROL_NOT_DONE;
        $stateReview = Constants::REVIEW_STATUS_NOT_DONE;

        if (!$visitTypeEntity->localFormNeeded) $stateInvestigatorForm = Constants::INVESTIGATOR_FORM_NOT_NEEDED;
        if (!$this->calculateIsNeeded($visitTypeEntity->qcProbability)) $stateQualityControl = Constants::QUALITY_CONTROL_NOT_NEEDED;
        if (!$this->calculateIsNeeded($visitTypeEntity->reviewProbability)) $stateReview = Constants::REVIEW_STATUS_NOT_NEEDED;

        $visitId = $this->visitRepositoryInterface->createVisit(
            $studyName,
            $creatorUserId,
            $patientId,
            $visitDate,
            $visitTypeId,
            $statusDone,
            $reasonForNotDone,
            $stateInvestigatorForm,
            $stateQualityControl,
            $stateReview
        );

        return $visitId;
    }

    /**
     * Generate random value between 0 and 100 and return if the value is below the probability threshold
     */
    private function calculateIsNeeded(int $probability): bool
    {
        $randomValue = random_int(1, 100);
        return ($randomValue <= $probability);
    }
}