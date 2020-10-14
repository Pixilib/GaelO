<?php

namespace App\GaelO\UseCases\CreateCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;

class CreateCenter {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){

        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;

    }

    public function execute(CreateCenterRequest $createCenterRequest, CreateCenterResponse $createCenterResponse){

        $code = $createCenterRequest->code;
        $name = $createCenterRequest->name;
        $countryCode = $createCenterRequest->countryCode;

        if($this->persistenceInterface->isKnownCenter($code)){
            $createCenterResponse->status = 409;
            $createCenterResponse->statusText = 'Conflict. Code already used.';
            return;

        };

        $this->persistenceInterface->createCenter($code, $name, $countryCode);

        $actionDetails = [
            'createdCenterCode'=>$code,
            'createdCenterName'=>$name,
            'createdCenterCountryCode'=>$countryCode
        ];

        $this->trackerService->writeAction($createCenterRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_CENTER, $actionDetails);

        $createCenterResponse->status = 201;
        $createCenterResponse->statusText = 'Created';


    }

}
