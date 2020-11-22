<?php

namespace App\GaelO\UseCases\GetPreference;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetPreferences {

    public function __construct(AuthorizationService $authorizationService) {
        $this->authorizationService = $authorizationService;

    }

    public function execute(GetPreferencesRequest $getPreferencesRequest, GetPreferencesResponse $getPreferencesResponse){

        try{

            $this->checkAuthorization($getPreferencesRequest->currentUserId);

            //Add preference defined in env file (read only)
            $preferences['platformName'] =LaravelFunctionAdapter::getConfig(SettingsConstants::PLATFORM_NAME);
            $preferences['adminEmail'] = LaravelFunctionAdapter::getConfig(SettingsConstants::MAIL_FROM_ADDRESS);
            $preferences['emailReplyTo'] = LaravelFunctionAdapter::getConfig(SettingsConstants::MAIL_REPLY_TO_DEFAULT);
            $preferences['corporation'] = LaravelFunctionAdapter::getConfig(SettingsConstants::CORPORATION);
            $preferences['url'] = LaravelFunctionAdapter::getConfig(SettingsConstants::URL);
            $preferences['patientCodeLength'] =  LaravelFunctionAdapter::getConfig(SettingsConstants::PATIENT_CODE_LENGTH);


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

    private function checkAuthorization(int $userId) : void {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }

}
