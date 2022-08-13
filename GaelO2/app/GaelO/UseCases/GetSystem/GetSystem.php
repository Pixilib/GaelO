<?php

namespace App\GaelO\UseCases\GetSystem;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetSystem
{

    private FrameworkInterface $frameworkInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(AuthorizationUserService $authorizationUserService, FrameworkInterface $frameworkInterface)
    {
        $this->authorizationUserService = $authorizationUserService;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(GetSystemRequest $getSystemRequest, GetSystemResponse $getSystemResponse)
    {

        try {
            $currentUserId = $getSystemRequest->currentUserId;

            $this->checkAuthorization($currentUserId);

            $preferences = [];

            $preferences['version'] = $this->frameworkInterface->getConfig('version');
            $preferences['platformName'] = $this->frameworkInterface->getConfig(SettingsConstants::PLATFORM_NAME);
            $preferences['adminEmail'] = $this->frameworkInterface->getConfig(SettingsConstants::MAIL_FROM_ADDRESS);
            $preferences['emailReplyTo'] = $this->frameworkInterface->getConfig(SettingsConstants::MAIL_REPLY_TO_DEFAULT);
            $preferences['corporation'] = $this->frameworkInterface->getConfig(SettingsConstants::CORPORATION);
            $preferences['url'] = $this->frameworkInterface->getConfig(SettingsConstants::APP_URL);

            $getSystemResponse->body = $preferences;
            $getSystemResponse->status = 200;
            $getSystemResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getSystemResponse->body = $e->getErrorBody();
            $getSystemResponse->status = $e->statusCode;
            $getSystemResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId)
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        }
    }
}
