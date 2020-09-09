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
        $preferences['plateformName'] =LaravelFunctionAdapter::getEnv('APP_NAME');
        $preferences['adminEmail'] = LaravelFunctionAdapter::getEnv('MAIL_FROM_ADDRESS');
        $preferences['emailReplyTo'] = LaravelFunctionAdapter::getEnv('MAIL_REPLY_TO_DEFAULT');
        $preferences['corporation'] = LaravelFunctionAdapter::getEnv('APP_CORPORATION');
        $preferences['url'] = LaravelFunctionAdapter::getEnv('APP_URL');

        $preferences['patientCodeLength'] = $preferencesDb['patient_code_length'];
        $preferences['parseDateImport'] = $preferencesDb['parse_date_import'];
        $preferences['parseCountryName'] = $preferencesDb['parse_country_name'];

        $getPreferencesResponse->body = $preferences;
        $getPreferencesResponse->status = 200;
        $getPreferencesResponse->statusText = 'OK';

    }

}
