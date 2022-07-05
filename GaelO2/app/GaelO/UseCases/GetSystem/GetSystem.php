<?php

namespace App\GaelO\UseCases\GetSystem;

use App\GaelO\Exceptions\GaelOException;
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

            $version = $this->frameworkInterface->getConfig('version');

            $response = [
                'version' => $version
            ];

            $getSystemResponse->body = $response;
            $getSystemResponse->status = 200;
            $getSystemResponse->statusText = 'OK';
        } catch (GaelOException $e) {
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
