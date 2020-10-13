<?php

namespace App\GaelO\UseCases\GetTrackerUser;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\UseCases\GetTrackerAdmin\TrackerEntity;

class GetTrackerUser {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetTrackerUserRequest $trackerRequest, GetTrackerUserResponse $trackerResponse) : void {

        $dbData = $this->persistenceInterface->getTrackerOfRole('User');
        $responseArray = [];
        foreach($dbData as $data){
            $responseArray[] = TrackerEntity::fillFromDBReponseArray($data);
        }
        $trackerResponse->body = $responseArray;
        $trackerResponse->status = 200;
        $trackerResponse->statusText = 'OK';
    }
}
