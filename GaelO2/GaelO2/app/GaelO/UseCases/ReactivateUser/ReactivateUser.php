<?php

namespace App\GaelO\UseCases\ReactivateUser;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\TrackerService;
use Exception;

class ReactivateUser{

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService, MailServices $mailServices){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
        $this->mailServices = $mailServices;
        $this->authorizationService = $authorizationService;
    }

    public function execute(ReactivateUserRequest $reactivateUserRequest, ReactivateUserResponse $reactivateUserResponse){

        try{

            $this->checkAuthorization($reactivateUserRequest->currentUserId);

            //Undelete User
            $this->persistenceInterface->reactivateUser($reactivateUserRequest->userId);
            //Generate new password and set status unconfirmed
            $user = $this->persistenceInterface->find($reactivateUserRequest->userId);
            $newPassword = substr(uniqid(), 1, 10);
            $user['password_temporary'] = LaravelFunctionAdapter::hash( $newPassword );
            $user['status'] = Constants::USER_STATUS_UNCONFIRMED;

            $this->persistenceInterface->updateUser($user['id'],
                $user['username'],
                $user['lastname'],
                $user['firstname'],
                $user['status'],
                $user['email'],
                $user['phone'],
                $user['administrator'],
                $user['center_code'],
                $user['job'],
                $user['orthanc_address'],
                $user['orthanc_login'],
                $user['orthanc_password'],
                $user['password_temporary'],
                $user['password'],
                $user['creation_date'],
                $user['last_password_update']
            );

            $this->mailServices->sendResetPasswordMessage(
                ($user['firstname'].' '.$user['lastname']),
                $user['username'],
                $newPassword,
                $user['email']
            );

            $actionsDetails = [
                'reactivatedUser' => $reactivateUserRequest->userId
            ];
            $this->trackerService->writeAction($reactivateUserRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_USER, $actionsDetails);

            $reactivateUserResponse->status = 200;
            $reactivateUserResponse->statusText = 'OK';

        } catch( GaelOException $e){

            $reactivateUserResponse->status = $e->statusCode;
            $reactivateUserResponse->statusText = $e->statusText;

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
