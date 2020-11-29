<?php

namespace App\GaelO\UseCases\DeleteAffiliatedCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;
use Exception;

class DeleteAffiliatedCenter {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService) {
        $this->persistenceInterface=$persistenceInterface;
        $this->trackerService=$trackerService;
        $this->authorizationService = $authorizationService;
    }

    public function execute(DeleteAffiliatedCenterRequest $deleteAffiliatedCenterRequest, DeleteAffiliatedCenterResponse $deleteAffiliatedCenterResponse){

        try{

            $this->checkAuthorization($deleteAffiliatedCenterRequest->currentUserId);

            $this->persistenceInterface->deleteAffiliatedCenter($deleteAffiliatedCenterRequest->userId, $deleteAffiliatedCenterRequest->centerCode);

            $actionDetails = [
                'deletedAffiliatedCenters' => $deleteAffiliatedCenterRequest->centerCode
            ];

            $this->trackerService->writeAction($deleteAffiliatedCenterRequest->userId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_USER, $actionDetails);

            $deleteAffiliatedCenterResponse->status = 200;
            $deleteAffiliatedCenterResponse->statusText = 'OK';

        }catch (GaelOException $e) {

            $deleteAffiliatedCenterResponse->status = $e->statusCode;
            $deleteAffiliatedCenterResponse->statusText = $e->statusText;
            $deleteAffiliatedCenterResponse->body = $e->getErrorBody();

        }catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $userId){
        $this->authorizationService->setCurrentUserAndRole($userId);
        if ( ! $this->authorizationService->isAdmin() ){
            throw new GaelOForbiddenException();
        }


    }

}
