<?php

namespace App\GaelO\UseCases\DeleteVisitGroup;

use App\GaelO\Interfaces\PersistenceInterface;

class DeleteVisitGroup {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(DeleteVisitGroupRequest $deleteVisitGroupRequest, DeleteVisitGroupResponse $deleteVisitGroupResponse){

        $hasVisitTypes = $this->persistenceInterface->hasVisitTypes($deleteVisitGroupRequest->visitGroupId);
        if($hasVisitTypes){
            $deleteVisitGroupResponse->body = ['errorMessage' => 'Existing Child Visit Type'];
            $deleteVisitGroupResponse->status = 403;
            $deleteVisitGroupResponse->statusText = "Forbidden";
        }else{
            $this->persistenceInterface->delete($deleteVisitGroupRequest->visitGroupId);
            $deleteVisitGroupResponse->status = 200;
            $deleteVisitGroupResponse->statusText = 'OK';
        }

    }
}
