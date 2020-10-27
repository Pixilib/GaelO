<?php

namespace App\GaelO\UseCases\GetTracker;

use App\GaelO\Interfaces\PersistenceInterface;

class GetTracker {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetTrackerRequest $getTrackerRequest, GetTrackerResponse $getTrackerResponse) : void {
        $admin = $getTrackerRequest->admin;
        if ($admin === 'true') {
            $dbData = $this->persistenceInterface->getTrackerOfRole('Administrator');
            $responseArray = [];
            foreach($dbData as $data){
                $responseArray[] = TrackerEntity::fillFromDBReponseArray($data);
            }
            $getTrackerResponse->body = $responseArray;
        } else {
            $dbData = $this->persistenceInterface->getTrackerOfRole('User');
            $responseArray = [];
            foreach($dbData as $data){
                $responseArray[] = TrackerEntity::fillFromDBReponseArray($data);
            }
            $getTrackerResponse->body = $responseArray;
        }

        $getTrackerResponse->status = 200;
        $getTrackerResponse->statusText = 'OK';
    }
}
