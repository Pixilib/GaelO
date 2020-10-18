<?php

namespace App\GaelO\UseCases\GetPreference;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Interfaces\PersistenceInterface;

class GetPreferences {

    public function __construct(PersistenceInterface $persistenceInterface) {
        $this->persistenceInterface = $persistenceInterface;

    }

    public function execute(GetPreferencesRequest $getPreferencesRequest, GetPreferencesResponse $getPreferencesResponse){

        $preferencesDb = $this->persistenceInterface->getAll();
        //Add preference defined in env file (read only)
        $preferences['platformName'] =LaravelFunctionAdapter::getConfig('name');
        $preferences['adminEmail'] = LaravelFunctionAdapter::getConfig('mailFromAddress');
        $preferences['emailReplyTo'] = LaravelFunctionAdapter::getConfig('mailReplyToDefault');
        $preferences['corporation'] = LaravelFunctionAdapter::getConfig('corporation');
        $preferences['url'] = LaravelFunctionAdapter::getConfig('url');

        $preferences['patientCodeLength'] = $preferencesDb['patient_code_length'];
        $preferences['parseDateImport'] = $preferencesDb['parse_date_import'];
        $preferences['parseCountryName'] = $preferencesDb['parse_country_name'];

        $getPreferencesResponse->body = $preferences;
        $getPreferencesResponse->status = 200;
        $getPreferencesResponse->statusText = 'OK';



    }

}
