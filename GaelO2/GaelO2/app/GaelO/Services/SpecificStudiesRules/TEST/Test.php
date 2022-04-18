<?php

use App\GaelO\Services\SpecificStudiesRules\AbstractGaelOStudy;
use App\GaelO\Services\SpecificStudiesRules\TEST\TEST_FDG_PET0;

class Test extends AbstractGaelOStudy {

    public function getSpecificForm(string $visitGroupName, string $visitTypeName): String
    {

        return TEST_FDG_PET0::class;

    }

}
