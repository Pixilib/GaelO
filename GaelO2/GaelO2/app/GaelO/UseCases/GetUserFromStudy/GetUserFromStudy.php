<?php

namespace App\GaelO\UseCases\GetUserFromStudy;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetUser\UserEntity;
use App\GaelO\UseCases\GetUserFromStudy\GetUserFromStudyRequest;
use App\GaelO\UseCases\GetUserFromStudy\GetUserFromStudyResponse;
use Exception;

class GetUserFromStudy {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetUserFromStudyRequest $userRequest, GetUserFromStudyResponse $userResponse) : void
    {
        try{
            $this->checkAuthorization($userRequest->currentUserId);
            $studyName = $userRequest->studyName;

            $dbData = $this->persistenceInterface->getUsersFromStudy($studyName);
            $responseArray = [];
            foreach($dbData as $data){
                $data = $data->toArray();
                $responseArray[] = UserEntity::fillFromDBReponseArray($data);
            }
            $userResponse->body = $responseArray;
            $userResponse->status = 200;
            $userResponse->statusText = 'OK';

        } catch (GaelOException $e){
            $userResponse->body = $e->getErrorBody();
            $userResponse->status = $e->statusCode;
            $userResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $userId)  {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }



}

?>
