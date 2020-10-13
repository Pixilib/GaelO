<?php

namespace App\GaelO\UseCases\GetTrackerAdmin;

use App\GaelO\Interfaces\PersistenceInterface;

class GetTrackerAdmin {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetTrackerAdminRequest $trackerRequest, GetTrackerAdminResponse $trackerResponse) : void {

        $dbData = $this->persistenceInterface->getTrackerOfRole('Administrator');
        $responseArray = [];
        foreach($dbData as $data){
            $responseArray[] = TrackerEntity::fillFromDBReponseArray($data);
        }
        $trackerResponse->body = $responseArray;
        $trackerResponse->status = 200;
        $trackerResponse->statusText = 'OK';
    }
}
