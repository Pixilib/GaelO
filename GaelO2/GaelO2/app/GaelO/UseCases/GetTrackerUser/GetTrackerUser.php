<?php

namespace App\GaelO\UseCases\GetTrackerUser;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\UseCases\GetTrackerAdmin\TrackerEntity;

class GetTrackerUser {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetTrackerUserRequest $trackerRequest, GetTrackerUserResponse $trackerResponse) : void {

        $admin = $trackerRequest->admin;

        if ($admin) {
            $dbData = $this->persistenceInterface->getAll();
            $responseArray = [];
            foreach($dbData as $data){
                $responseArray[] = TrackerEntity::fillFromDBReponseArray($data);
            }
            $trackerResponse->body = $responseArray;
        } else {
            $dbData = $this->persistenceInterface->get();
            $responseEntity = TrackerEntity::fillFromDBReponseArray($dbData);
            $trackerResponse->body = $responseEntity;
        }
        $trackerResponse->status = 200;
        $trackerResponse->statusText = 'OK';
    }
}
