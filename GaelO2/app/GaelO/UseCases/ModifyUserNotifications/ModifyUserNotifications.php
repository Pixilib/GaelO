<?php

namespace App\GaelO\UseCases\ModifyUserNotifications;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use Exception;

class ModifyUserNotifications
{
    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
    }


    public function execute(ModifyUserNotificationsRequest $modifyUserNotificationsRequest, ModifyUserNotificationsResponse $modifyUserNotificationsResponse)
    {

        try {
            $id = $modifyUserNotificationsRequest->userId;
            $this->checkAuthorization($modifyUserNotificationsRequest->currentUserId, $id);
            $notificationsIds = $modifyUserNotificationsRequest->notificationsIds;

            $this->userRepositoryInterface->markUserNotificationsRead($id, $notificationsIds);

            $modifyUserNotificationsResponse->status = 200;
            $modifyUserNotificationsResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $modifyUserNotificationsResponse->body = $e->getErrorBody();
            $modifyUserNotificationsResponse->status = $e->statusCode;
            $modifyUserNotificationsResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, int $calledUserId)
    {
        if ($calledUserId !== $userId) {
            throw new GaelOForbiddenException();
        }
    }
}
