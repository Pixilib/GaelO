<?php

namespace App\GaelO\UseCases\DeleteAffiliatedCenter;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;

class DeleteAffiliatedCenter {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService) {
        $this->persistenceInterface=$persistenceInterface;
        $this->trackerService=$trackerService;
    }

    public function execute(DeleteAffiliatedCenterRequest $deleteAffiliatedCenterRequest, DeleteAffiliatedCenterResponse $deleteAffiliatedCenterResponse){
        $this->persistenceInterface->deleteAffiliatedCenter($deleteAffiliatedCenterRequest->userId, $deleteAffiliatedCenterRequest->centerCode);

        //SK TRACKER A FAIRE

        $deleteAffiliatedCenterResponse->status = 200;
        $deleteAffiliatedCenterResponse->statusText = 'OK';
    }

}
