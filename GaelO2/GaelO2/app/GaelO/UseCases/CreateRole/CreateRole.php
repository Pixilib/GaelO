<?php
namespace App\GaelO\UseCases\CreateRole;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;

class CreateRole {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
    }

    public function execute(CreateRoleRequest $createRoleRequest, CreateRoleResponse $createRoleResponse){
        $user = $this->persistenceInterface->find($createRoleRequest->userId);

    }
}
