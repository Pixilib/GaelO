<?php

namespace App\GaelO\UseCases\GetPreference;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetPreferences {

    private FrameworkInterface $frameworkInterface;

    public function __construct(AuthorizationService $authorizationService, FrameworkInterface $frameworkInterface) {
        $this->authorizationService = $authorizationService;
        $this->frameworkInterface = $frameworkInterface;

    }

    public function execute(GetPreferencesRequest $getPreferencesRequest, GetPreferencesResponse $getPreferencesResponse){

        try{

            $this->checkAuthorization($getPreferencesRequest->currentUserId);

            //Add preference defined in env file (read only)
            $preferences['platformName'] = $this->frameworkInterface::getConfig(SettingsConstants::PLATFORM_NAME);
            $preferences['adminEmail'] = $this->frameworkInterface::getConfig(SettingsConstants::MAIL_FROM_ADDRESS);
            $preferences['emailReplyTo'] = $this->frameworkInterface::getConfig(SettingsConstants::MAIL_REPLY_TO_DEFAULT);
            $preferences['corporation'] = $this->frameworkInterface::getConfig(SettingsConstants::CORPORATION);
            $preferences['url'] = $this->frameworkInterface::getConfig(SettingsConstants::APP_URL);

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
