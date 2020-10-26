<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\VisitTypeRepository;
use App\GaelO\Repositories\VisitRepository;

class VisitService
{
    /**
     * Import patient in study
     */

    public array $successList = [];
    public array $failList = [];

    public function __construct(VisitRepository $visitRepository, VisitTypeRepository $visitTypeRepository)
    {
        $this->visitTypeRepository = $visitTypeRepository;
        $this->visitRepository = $visitRepository;
    }

    public function createVisit(
        int $creatorUserId,
        int $patientCode,
        ?string $acquisitionDate,
        int $visitTypeId,
        string $statusDone,
        ?string $reasonForNotDone
    ) {

        $visitTypeEntity = $this->visitTypeRepository->getEntity($visitTypeId);

        $stateInvestigatorForm = Constants::INVESTIGATOR_FORM_NOT_DONE;
        $stateQualityControl = Constants::QUALITY_CONTROL_NOT_DONE;

        if (!$visitTypeEntity->localFormNeeded) $stateInvestigatorForm = Constants::INVESTIGATOR_FORM_NOT_NEEDED;
        if (!$visitTypeEntity->qcNeeded) $stateQualityControl = Constants::QUALITY_CONTROL_NOT_NEEDED;

        $this->visitRepository->createVisit(
            $creatorUserId,
            $patientCode,
            $acquisitionDate,
            $visitTypeId,
            $statusDone,
            $reasonForNotDone,
            $stateInvestigatorForm,
            $stateQualityControl
        );
    }
}
