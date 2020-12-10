<?php

namespace App\GaelO\UseCases\GetUser;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetUser\GetUserRequest;
use App\GaelO\UseCases\GetUser\GetUserResponse;
use Exception;

class GetUser {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetUserRequest $getUserRequest, GetUserResponse $getUserResponse) : void
    {

        try{

            $id = $getUserRequest->id;

            $this->checkAuthorization($getUserRequest->currentUserId, $id);

            if ($id == 0) {
                $dbData = $this->persistenceInterface->getAll();
                $responseArray = [];
                foreach($dbData as $data){
                    $responseArray[] = UserEntity::fillFromDBReponseArray($data);
                }
                $getUserResponse->body = $responseArray;
            } else {
                $dbData = $this->persistenceInterface->find($id);
                $responseEntity = UserEntity::fillFromDBReponseArray($dbData);
                $getUserResponse->body = $responseEntity;
            }
            $getUserResponse->status = 200;
            $getUserResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $getUserResponse->body = $e->getErrorBody();
            $getUserResponse->status = $e->statusCode;
            $getUserResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $userId, int $calledUserId)  {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( $calledUserId !== $userId && ! $this->authorizationService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }

}

?>
