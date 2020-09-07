<?php

namespace App\GaelO\UseCases\GetUserRoles;

use App\GaelO\Interfaces\PersistenceInterface;

class GetUserRoles {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetUserRolesRequest $getUserRolesRequest, GetUserRolesResponse $getUserRoleResponse) : void {

        if( empty($getUserRolesRequest->study) ){
            $roles = $this->persistenceInterface->getUsersRoles($getUserRolesRequest->userId);
        }else {
            $roles = $this->persistenceInterface->getUsersRolesInStudy($getUserRolesRequest->userId, $getUserRolesRequest->study);
        }

        $getUserRoleResponse->body = $roles;
        $getUserRoleResponse->status = 200;
        $getUserRoleResponse->statusText = 'OK';

    }
}
