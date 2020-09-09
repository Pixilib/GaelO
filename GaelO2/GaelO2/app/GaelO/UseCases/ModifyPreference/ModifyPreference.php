<?php

namespace App\GaelO\UseCases\ModifyPreference;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;

class ModifyPreference {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
    }

    public function execute(ModifyPreferenceRequest $modifyPreferenceRequest, ModifyPreferenceResponse $modifyPrefrenceResponse){

        $this->persistenceInterface->updatePreferences($modifyPreferenceRequest->patientCodeLength,
                $modifyPreferenceRequest->parseDateImport,
                $modifyPreferenceRequest->parseCountryName);

        $modifyPrefrenceResponse->status=200;
        $modifyPrefrenceResponse->statusText='OK';

    }

}
