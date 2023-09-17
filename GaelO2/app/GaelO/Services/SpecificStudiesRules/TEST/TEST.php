<?php

namespace App\GaelO\Services\SpecificStudiesRules\TEST;

use App\GaelO\Services\GaelOStudiesService\AbstractGaelOStudy;
use App\GaelO\Services\GaelOStudiesService\DefaultVisitRules;
use App\GaelO\Services\GaelOStudiesService\Events\BaseStudyEvent;
use App\GaelO\Services\GaelOStudiesService\Events\VisitUploadedEvent;

class TEST extends AbstractGaelOStudy {

    const TEST_LOCALIZATIONS = [
        1,
        2,
        3,
        4,
        5,
        6,
        7,
        8,
        9,
        10,
        11,
        12,
        13,
        14,
        15,
        16,
        17,
        18,
        19,
        20,
        21,
        22,
        23,
        25,
        27,
        28,
        29,
        31,
        33,
        35,
        37,
        38,
        39,
        41,
        42,
        43,
        44,
        45,
        46,
        47,
        48,
        49,
        50,
        51,
        52,
        53,
        54,
        55,
        56,
        57,
        58,
        59,
        60,
        61,
        62,
        63,
        64,
        65,
        66,
        67
    ];

    public function getVisitRulesClass(string $visitGroupName, string $visitTypeName): String
    {
        $key = $visitGroupName.'_'.$visitTypeName;
        if($key === TEST_VISITS::PET0->value) return TEST_FDG_PET0::class;
        else if ($key === TEST_VISITS::CT0->value) return TEST_WB_CT0::class;
        else return DefaultVisitRules::class;

    }

    public function onEventStudy(BaseStudyEvent $studyEvent): void
    {
        parent::onEventStudy($studyEvent);
        if ($studyEvent instanceof VisitUploadedEvent) {
           $this->jobInterface->sendRadiomicsReport($studyEvent->getVisitId());
        }
    }

}


enum TEST_VISITS: string
{
    case PET0 = "FDG_PET_0";
    case CT0 = "WB_CT0";
}
