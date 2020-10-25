<?php
namespace App\GaelO\UseCases\CreateUserRoles;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
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

        $this->authorizationService->isAdmin($createRoleRequest->userId);
        //Get current roles in study for users
        $actualRolesArray = $this->persistenceInterface->getUsersRolesInStudy($createRoleRequest->userId, $createRoleRequest->study);
        //Get request role to be add
        $requestRolesArray = $createRoleRequest->roles;
        //compute only new roles to be add in database
        $newRoles = array_diff($requestRolesArray, $actualRolesArray);

        if(empty($newRoles)){
            throw new GaelOException("No New Roles");
        }

        //Write in database and return sucess response (error will be handled by laravel)
        $this->persistenceInterface->addUserRoleInStudy($createRoleRequest->userId, $createRoleRequest->study, $newRoles);
        $actionDetails = [
            "Add Roles"=> $newRoles
        ];
        $this->trackerService->writeAction( $createRoleRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, $createRoleRequest->study, null, Constants::TRACKER_EDIT_USER, $actionDetails);
        $createRoleResponse->statusText = "Created";
        $createRoleResponse->status = 201;

    }
}
