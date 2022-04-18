<?php

namespace App\GaelO\Services\SpecificStudiesRules\TEST;

use App\GaelO\Services\SpecificStudiesRules\AbstractGaelOStudy;
use App\GaelO\Services\SpecificStudiesRules\TEST\TEST_FDG_PET0;

class TEST extends AbstractGaelOStudy {

    public function getSpecificForm(string $visitGroupName, string $visitTypeName): String
    {
        $key = $visitGroupName.'_'.$visitTypeName;
        if($key === TEST_VISITS::PET0->value) return TEST_FDG_PET0::class;
        else if ($key === TEST_VISITS::CT0->value) return TEST_WB_CT0::class;
        else return null;

    }

}


enum TEST_VISITS: string
{
    case PET0 = "FDG_PET_0";
    case CT0 = "WB_CT0";
}
