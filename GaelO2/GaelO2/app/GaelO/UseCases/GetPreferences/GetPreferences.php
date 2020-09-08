<?php

namespace App\GaelO\UseCases\GetPreferences;

use App\GaelO\Interfaces\PersistenceInterface;

class GetPreferences {

    public function __construct(PersistenceInterface $persistenceInterface) {
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetPreferencesRequest $getPreferencesRequest, GetPreferencesResponse $getPreferencesResponse){

        $preferences = $this->persistenceInterface->getAll();
        $getPreferencesResponse->body = $preferences;
        $getPreferencesResponse->status = 200;
        $getPreferencesResponse->statusText = 'OK';

    }

}
