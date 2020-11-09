<?php
namespace App\GaelO\UseCases\CreateUserRoles;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;

class CreateUserRoles {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
        $this->authorizationService = $authorizationService;
    }

    public function execute(CreateUserRolesRequest $createRoleRequest, CreateUserRolesResponse $createRoleResponse){

        try{

            $this->checkAuthorization($createRoleRequest);

            //Get current roles in study for users
            $actualRolesArray = $this->persistenceInterface->getUsersRolesInStudy($createRoleRequest->userId, $createRoleRequest->study);


            if( in_array($createRoleRequest->role, $actualRolesArray) ) {
                throw new GaelOBadRequestException("Already Existing Role");
            }

            //Write in database and return sucess response (error will be handled by laravel)
            $this->persistenceInterface->addUserRoleInStudy($createRoleRequest->userId, $createRoleRequest->study, $createRoleRequest->role);
            $actionDetails = [
                "Add Roles"=> $createRoleRequest->role
            ];
            $this->trackerService->writeAction( $createRoleRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, $createRoleRequest->study, null, Constants::TRACKER_EDIT_USER, $actionDetails);

            $createRoleResponse->statusText = "Created";
            $createRoleResponse->status = 201;

        } catch (GaelOException $e){
            $createRoleResponse->statusText = $e->statusText;
            $createRoleResponse->status = $e->statusCode;
            $createRoleResponse->body = $e->getErrorBody();
        }

    }

    private function checkAuthorization(CreateUserRolesRequest $createRoleRequest){
        $this->authorizationService->setCurrentUser($createRoleRequest->currentUserId);
        if( ! $this->authorizationService->isAdmin($createRoleRequest->currentUserId) ) {
            throw new GaelOForbiddenException();
        };
    }
}
