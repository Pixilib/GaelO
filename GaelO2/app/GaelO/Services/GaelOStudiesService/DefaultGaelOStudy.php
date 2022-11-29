<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Exceptions\GaelOException;

class DefaultGaelOStudy extends AbstractGaelOStudy{

    public function getVisitRulesClass(string $visitGroupName, string $visitTypeName): string {
        throw new GaelOException("No Specific Form in Default Study Object");
    }

}
