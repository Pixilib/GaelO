<?php

namespace App\GaelO\UseCases\ModifyPreference;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;

class ModifyPreference {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
    }

    public function execute(ModifyPreferenceRequest $modifyPreferenceRequest, ModifyPrefrenceResponse $modifyPrefrenceResponse){

        //SK EMAIL A VOIR PEUT ETRE DOIT VENIR EN .env
        //A REVOIR ICI A PRIORI LES MAILS INUTILES et PLATEFORM NAME AUSSI
        //URL en env
        //A priori garder que code length, parse date import et parse country name=>qui seront servi par un service
        $newPreferences = [
            'patient_code_length'=>$modifyPreferenceRequest->patientCodeLength,
            'plateform_name'=>$modifyPreferenceRequest->plateformeName,
            'admin_email'=>$modifyPreferenceRequest->adminEmail,
            'email_reply_to'=>$modifyPreferenceRequest->replyToEmail,
            'corporation'=>$modifyPreferenceRequest->corporation,
            'url'=>$modifyPreferenceRequest->url,
            'parse_date_import'=>$modifyPreferenceRequest->parseDateImport,
            'parse_country_name'=>$modifyPreferenceRequest->parseCountryName
        ];

    }

}
