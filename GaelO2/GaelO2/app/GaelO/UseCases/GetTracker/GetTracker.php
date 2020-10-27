<?php

namespace App\GaelO\UseCases\GetTracker;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;
use Illuminate\Support\Facades\Log;

class GetTracker {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetTrackerRequest $getTrackerRequest, GetTrackerResponse $getTrackerResponse) : void {
        $admin = $getTrackerRequest->admin;
        if (filter_var($admin, FILTER_VALIDATE_BOOLEAN)) $dbData = $this->persistenceInterface->getTrackerOfRole(Constants::TRACKER_ROLE_ADMINISTRATOR);
        else $dbData = $this->persistenceInterface->getTrackerOfRole(Constants::TRACKER_ROLE_USER);
        $responseArray = [];
        foreach($dbData as $data){
            $responseArray[] = TrackerEntity::fillFromDBReponseArray($data);
        }
        $getTrackerResponse->body = $responseArray;
        $getTrackerResponse->status = 200;
        $getTrackerResponse->statusText = 'OK';
    }
}
