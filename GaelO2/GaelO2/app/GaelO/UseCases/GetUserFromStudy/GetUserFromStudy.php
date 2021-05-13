<?php

namespace App\GaelO\UseCases\GetUserFromStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetUser\UserEntity;
use App\GaelO\UseCases\GetUserFromStudy\GetUserFromStudyRequest;
use App\GaelO\UseCases\GetUserFromStudy\GetUserFromStudyResponse;
use Exception;

class GetUserFromStudy {

    private AuthorizationService $authorizationService;
    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationService $authorizationService){
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetUserFromStudyRequest $userRequest, GetUserFromStudyResponse $userResponse) : void
    {
        try{

            $studyName = $userRequest->studyName;
            $this->checkAuthorization($userRequest->currentUserId, $studyName);

            $dbData = $this->userRepositoryInterface->getUsersFromStudy($studyName);

            $responseArray = [];
            foreach($dbData as $data){
                $userEntity = UserEntity::fillMinimalFromDBReponseArray($data);
                $rolesArray = array_map(function($roleData) use ($studyName){
                    if($roleData['study_name'] == $studyName) return $roleData['name'];
                    else return null;
                }, $data ['roles']);
                //filter empty location
                $rolesArray = array_filter($rolesArray, function($role) {
                    if($role === null) return false;
                    else return true;
                } );
                //Rearange array to start as 0 without associative keys
                $rolesArray = array_values($rolesArray);
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
