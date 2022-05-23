<?php

namespace App\GaelO\Services\GaelOStudiesService;

use Exception;

class DefaultGaelOStudy extends AbstractGaelOStudy{

    public function getSpecificForm(string $visitGroupName, string $visitTypeName): string {
        throw new Exception("No Specific Form in Default Study Object");
    }

}
