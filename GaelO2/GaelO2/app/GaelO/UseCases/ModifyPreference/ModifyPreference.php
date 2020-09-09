<?php

namespace App\GaelO\UseCases\ModifyPreference;

use App\GaelO\Constants\Constants;
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

        $actionDetails=[
            'patient_code_length' => $modifyPreferenceRequest->patientCodeLength,
            'parse_date_import'=> $modifyPreferenceRequest->parseDateImport,
            'parse_country_name'=> $modifyPreferenceRequest->parseCountryName
        ];
        $this->trackerService->writeAction($modifyPreferenceRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_PREFERENCE, $actionDetails);

        $modifyPrefrenceResponse->status=200;
        $modifyPrefrenceResponse->statusText='OK';

    }

}
