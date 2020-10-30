<?php

namespace App\GaelO\UseCases\AddAffiliatedCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;

class AddAffiliatedCenter {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
    }

    public function execute(AddAffiliatedCenterRequest $addAffiliatedCenterRequest, AddAffiliatedCenterResponse $addAffiliatedCenterResponse){

        $existingCenter = $this->persistenceInterface->getAffiliatedCenter($addAffiliatedCenterRequest->userId);

        //Check the request creation is not in Main or affiliated centers
        if($addAffiliatedCenterRequest->centerCode === $existingCenter){
            $addAffiliatedCenterResponse->body = ['errorMessage' => 'Center already affiliated to user'];
            $addAffiliatedCenterResponse->status = 500;
            $addAffiliatedCenterResponse->statusText = "Internal Server Error";
            //throw new GaelOException("Center already affiliated to user");
        }

        $this->persistenceInterface->addAffiliatedCenter($addAffiliatedCenterRequest->userId, $addAffiliatedCenterRequest->centerCode);

        $actionDetails = [
            'addAffiliatedCenters' => $addAffiliatedCenterRequest->centerCode
        ];

        $this->trackerService->writeAction($addAffiliatedCenterRequest->userId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_USER, $actionDetails);


        $addAffiliatedCenterResponse->status = '201';
        $addAffiliatedCenterResponse->statusText = 'Created';
    }
}
