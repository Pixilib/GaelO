<?php
namespace App\GaelO\UseCases\CreateUserRoles;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;

class CreateUserRoles {

    private UserRepositoryInterface $userRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface){
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(CreateUserRolesRequest $createRoleRequest, CreateUserRolesResponse $createRoleResponse){

        try{

            $this->checkAuthorization($createRoleRequest->currentUserId);

            //Get current roles in study for users
            $actualRolesArray = $this->userRepositoryInterface->getUsersRolesInStudy($createRoleRequest->userId, $createRoleRequest->study);


            if( in_array($createRoleRequest->role, $actualRolesArray) ) {
                throw new GaelOBadRequestException("Already Existing Role");
            }

            //Write in database and return sucess response (error will be handled by laravel)
            $this->userRepositoryInterface->addUserRoleInStudy($createRoleRequest->userId, $createRoleRequest->study, $createRoleRequest->role);
            $actionDetails = [
                "Add Roles"=> $createRoleRequest->role
            ];
            $this->trackerRepositoryInterface->writeAction( $createRoleRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, $createRoleRequest->study, null, Constants::TRACKER_EDIT_USER, $actionDetails);

            $createRoleResponse->statusText = "Created";
            $createRoleResponse->status = 201;

        } catch (GaelOException $e){
            $createRoleResponse->statusText = $e->statusText;
            $createRoleResponse->status = $e->statusCode;
            $createRoleResponse->body = $e->getErrorBody();
        }

    }

    private function checkAuthorization(int $userId) : void {
        $this->authorizationUserService->setUserId($userId);
        if( ! $this->authorizationUserService->isAdmin($userId) ) {
            throw new GaelOForbiddenException();
        };
    }
}
