<?php

namespace App\GaelO\UseCases\GetPreference;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetPreferences {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService) {
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;

    }

    public function execute(GetPreferencesRequest $getPreferencesRequest, GetPreferencesResponse $getPreferencesResponse){

        try{

            $this->checkAuthorization($getPreferencesRequest->currentUserId);

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

        } catch (GaelOException $e ){

            $getPreferencesResponse->body = $e->getErrorBody();
            $getPreferencesResponse->status = $e->statusCode;
            $getPreferencesResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization($userId)  {
        $this->authorizationService->setCurrentUser($userId);
        if( ! $this->authorizationService->isAdmin($userId)) {
            throw new GaelOForbiddenException();
        };
    }

}
