<?php

namespace App\GaelO\UseCases\DeleteUser;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Exceptions\GaelONotFoundException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;
use App\GaelO\UseCases\DeleteUser\DeleteUserRequest;
use App\GaelO\UseCases\DeleteUser\DeleteUserResponse;
use Exception;

class DeleteUser {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService  = $trackerService;
        $this->authorizationService = $authorizationService;
    }

    public function execute(DeleteUserRequest $deleteRequest, DeleteUserResponse $deleteResponse) : void {

        try{

            $this->checkAuthorization($deleteRequest->currentUserId);

            $this->persistenceInterface->delete($deleteRequest->id);

            $actionsDetails = [
                'deactivated_user'=>$deleteRequest->id
            ];

            $this->trackerService->writeAction($deleteRequest->currentUserId,
                                    Constants::TRACKER_ROLE_USER, null, null,
                                    Constants::TRACKER_EDIT_USER, $actionsDetails);

            $deleteResponse->status = 200;
            $deleteResponse->statusText = 'OK';

        }catch ( GaelOException $e ) {
            $deleteResponse->status = $e->statusCode;
            $deleteResponse->statusText = $e->statusText;
            $deleteResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        };

    }

    private function checkAuthorization(int $userId) : void {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }

}

?>
