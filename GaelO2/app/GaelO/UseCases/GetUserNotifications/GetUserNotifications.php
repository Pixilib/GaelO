<?php

namespace App\GaelO\UseCases\GetUserNotifications;

use App\GaelO\Entities\NotificationEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use Exception;

class GetUserNotifications
{

    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
    }


    public function execute(GetUserNotificationsRequest $getUserNotificationsRequest, GetUserNotificationsResponse $getUserNotificationsResponse)
    {

        try {

            $id = $getUserNotificationsRequest->userId;
            $onlyUnread = $getUserNotificationsRequest->onlyUnread;
            $this->checkAuthorization($getUserNotificationsRequest->currentUserId, $id);

            $notificationsEntities = [];
            $notifications = $this->userRepositoryInterface->getUserNotifications($id, $onlyUnread);

            foreach ($notifications as $notification) {
                $notificationsEntities[] = NotificationEntity::fillFromDBReponseArray($notification);
            }

            $getUserNotificationsResponse->status = 200;
            $getUserNotificationsResponse->statusText = 'OK';
            $getUserNotificationsResponse->body = $notificationsEntities;
        } catch (AbstractGaelOException $e) {
            $getUserNotificationsResponse->body = $e->getErrorBody();
            $getUserNotificationsResponse->status = $e->statusCode;
            $getUserNotificationsResponse->statusText = $e->statusText;
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
