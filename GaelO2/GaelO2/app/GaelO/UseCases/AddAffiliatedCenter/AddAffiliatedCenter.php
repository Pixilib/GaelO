<?php

namespace App\GaelO\UseCases\AddAffiliatedCenter;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;

class AddAffiliatedCenter {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
    }

    public function execute(AddAffiliatedCenterRequest $addAffiliatedCenterRequest, AddAffiliatedCenterResponse $addAffiliatedCenterResponse){

        //Sk Verifier que le centre pas deja dans la liste
        //Sk autoriser plusieurs centre d'un coup ?
        $this->persistenceInterface->addAffiliatedCenter($addAffiliatedCenterRequest->userId, $addAffiliatedCenterRequest->centerCode);

        //SK faire tracker

        $addAffiliatedCenterResponse->status = '201';
        $addAffiliatedCenterResponse->statusText = 'Created';
    }
}
