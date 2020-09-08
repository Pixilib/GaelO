<?php
namespace App\GaelO\UseCases\CreateUserRoles;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;
use Exception;

class CreateUserRoles {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
    }

    public function execute(CreateUserRolesRequest $createRoleRequest, CreateUserRolesResponse $createRoleResponse){

        //Get current roles in study for users
        $actualRolesArray = $this->persistenceInterface->getUsersRolesInStudy($createRoleRequest->userId, $createRoleRequest->study);
        //Get request role to be add
        $requestRolesArray = $createRoleRequest->roles;
        //compute only new roles to be add in database
        $newRoles = array_diff($requestRolesArray, $actualRolesArray);

        if(empty($newRoles)){
            $createRoleResponse->statusText = "No New Roles";
            $createRoleResponse->status = 400;
            return;
        }

        //Write in database and return sucess response (error will be handled by laravel)
        $this->persistenceInterface->addUserRoleInStudy($createRoleRequest->userId, $createRoleRequest->study, $newRoles);
        $createRoleResponse->statusText = "Created";
        $createRoleResponse->status = 201;

    }
}
