<?php

namespace App\GaelO\UseCases\AddAffiliatedCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;

class AddAffiliatedCenter {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
        $this->authorizationService = $authorizationService;
    }

    public function execute(AddAffiliatedCenterRequest $addAffiliatedCenterRequest, AddAffiliatedCenterResponse $addAffiliatedCenterResponse){

        if( $this->authorizationService->isAdmin($addAffiliatedCenterRequest->currentUserId) ) {

            $existingAffiliatingCenter = $this->persistenceInterface->getAffiliatedCenter($addAffiliatedCenterRequest->userId);
            $mainUserCenterCode = $this->persistenceInterface->find($addAffiliatedCenterRequest->userId)['center_code'];
            $existingCenterCodeArray  = array_map( function($center) { return $center['code']; }, $existingAffiliatingCenter);
            array_push($existingCenterCodeArray, $mainUserCenterCode);

            //Check the request creation is not in Main or affiliated centers
            if( ! in_array($addAffiliatedCenterRequest->centerCode, $existingCenterCodeArray) ){

                $this->persistenceInterface->addAffiliatedCenter($addAffiliatedCenterRequest->userId, $addAffiliatedCenterRequest->centerCode);

                $actionDetails = [
                    'addAffiliatedCenters' => $addAffiliatedCenterRequest->centerCode
                ];
                $this->trackerService->writeAction($addAffiliatedCenterRequest->userId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_USER, $actionDetails);

                $addAffiliatedCenterResponse->status = '201';
                $addAffiliatedCenterResponse->statusText = 'Created';

            } else {
                $addAffiliatedCenterResponse->body = ['errorMessage' => 'Center already affiliated to user'];
                $addAffiliatedCenterResponse->status = 409;
                $addAffiliatedCenterResponse->statusText = "Conflict";
            }

        } else {
            $addAffiliatedCenterResponse->status = 403;
            $addAffiliatedCenterResponse->statusText = 'Forbidden';
        };

    }
}
