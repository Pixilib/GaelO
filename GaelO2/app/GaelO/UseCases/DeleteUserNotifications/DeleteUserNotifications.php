<?php

namespace App\GaelO\UseCases\DeleteUserNotifications;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use Exception;

class DeleteUserNotifications
{

    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
    }


    public function execute(DeleteUserNotificationsRequest $deleteUserNotificationsRequest, DeleteUserNotificationsResponse $deleteUserNotificationsResponse)
    {

        try {
            $id = $deleteUserNotificationsRequest->userId;
            $this->checkAuthorization($deleteUserNotificationsRequest->currentUserId, $id);
            $notificationsIds = $deleteUserNotificationsRequest->notificationsIds;

            $this->userRepositoryInterface->deleteUserNotifications($id, $notificationsIds);

            $deleteUserNotificationsResponse->status = 200;
            $deleteUserNotificationsResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $deleteUserNotificationsResponse->body = $e->getErrorBody();
            $deleteUserNotificationsResponse->status = $e->statusCode;
            $deleteUserNotificationsResponse->statusText = $e->statusText;
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