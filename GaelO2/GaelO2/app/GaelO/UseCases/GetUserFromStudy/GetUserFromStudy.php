<?php

namespace App\GaelO\UseCases\GetUserFromStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetUser\UserEntity;
use App\GaelO\UseCases\GetUserFromStudy\GetUserFromStudyRequest;
use App\GaelO\UseCases\GetUserFromStudy\GetUserFromStudyResponse;
use Exception;

class GetUserFromStudy {
    private AuthorizationService $authorizationService;

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetUserFromStudyRequest $userRequest, GetUserFromStudyResponse $userResponse) : void
    {
        try{

            $studyName = $userRequest->studyName;
            $this->checkAuthorization($userRequest->currentUserId, $studyName);

            $dbData = $this->persistenceInterface->getUsersFromStudy($studyName);

            $responseArray = [];
            foreach($dbData as $data){
                $userEntity = UserEntity::fillFromDBReponseArray($data);
                $rolesArray = array_map(function($roleData){return $roleData['name'];}, $data ['roles']);
                $userEntity->addRoles($rolesArray);
                $responseArray[] = $userEntity;
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

    private function checkAuthorization(int $userId, string $studyName)  {
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        if(  ! $this->authorizationService->isRoleAllowed($studyName) && ! $this->authorizationService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }

}

?>
