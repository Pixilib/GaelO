<?php

namespace App\GaelO\UseCases\ModifyUserIdentification;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\ModifyUserIdentification\ModifyUserIdentificationRequest;
use App\GaelO\UseCases\ModifyUserIdentification\ModifyUserIdentificationResponse;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\TrackerService;
use App\GaelO\Services\UserService;
use Exception;

class ModifyUserIdentification {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService, MailServices $mailService, UserService $userService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
        $this->trackerService = $trackerService;
        $this->mailService = $mailService;
        $this->userService = $userService;
    }

    public function execute(ModifyUserIdentificationRequest $modifyUserIdentificationRequest, ModifyUserIdentificationResponse $modifyUserIdentificationResponse) : void {

        try{

            $this->checkAuthorization($modifyUserIdentificationRequest->currentUserId, $modifyUserIdentificationRequest->userId);

            $this->userService->patchUser($modifyUserIdentificationRequest);

            $details = [
                'modified_user_id'=>$modifyUserIdentificationRequest->userId,
                'username'=>$modifyUserIdentificationRequest->username,
                'lastname'=>$modifyUserIdentificationRequest->lastname,
                'firstname'=>$modifyUserIdentificationRequest->firstname,
                'email'=>$modifyUserIdentificationRequest->email,
                'phone'=>$modifyUserIdentificationRequest->phone
            ];

            $this->trackerService->writeAction($modifyUserIdentificationRequest->currentUserId, Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_EDIT_USER, $details);

            $modifyUserIdentificationResponse->status = 200;
            $modifyUserIdentificationResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $modifyUserIdentificationResponse->status = $e->statusCode;
            $modifyUserIdentificationResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $userId){
        if($currentUserId !== $userId) throw new GaelOForbiddenException();

    }

}

?>
