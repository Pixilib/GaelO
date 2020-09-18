<?php

namespace App\GaelO\UseCases\GetVisitGroup;

use App\GaelO\Interfaces\PersistenceInterface;

class GetVisitGroup {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetVisitGroupRequest $getVisitGroupRequest, GetVisitGroupResponse $getVisitTypeResponse){

        $visitGroupData = $this->persistenceInterface->find($getVisitGroupRequest->visitGroupId);
        $visitGroupEntity = VisitGroupEntity::fillFromDBReponseArray($visitGroupData);
        $getVisitTypeResponse->body = $visitGroupEntity;
        $getVisitTypeResponse->status = 200;
        $getVisitTypeResponse->statusText = 'OK';

    }
}
