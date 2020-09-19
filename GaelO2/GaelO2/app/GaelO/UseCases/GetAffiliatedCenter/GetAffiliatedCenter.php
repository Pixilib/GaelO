<?php

namespace App\GaelO\UseCases\GetAffiliatedCenter;

use App\GaelO\Interfaces\PersistenceInterface;

class GetAffiliatedCenter {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetAffiliatedCenterRequest $getAffiliatedCenterRequest, GetAffiliatedCenterResponse $getAffiliatedCenterResponse){

        $affiliatedCenters = $this->persistenceInterface->getAffiliatedCenter($getAffiliatedCenterRequest->userId);
        $centerResponseArray = [];

        foreach($affiliatedCenters as $center){
            $centerResponseArray[]  = CenterEntity::fillFromDBReponseArray($center);
        }

        $getAffiliatedCenterResponse->body =  $centerResponseArray;
        $getAffiliatedCenterResponse->status = 200;
        $getAffiliatedCenterResponse->statusText = 'OK';
    }


}
