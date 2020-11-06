<?php

namespace App\GaelO\UseCases\AddAffiliatedCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;
use Exception;

class AddAffiliatedCenter {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
        $this->authorizationService = $authorizationService;
    }

    public function execute(AddAffiliatedCenterRequest $addAffiliatedCenterRequest, AddAffiliatedCenterResponse $addAffiliatedCenterResponse){

        try{
            $this->checkAuthorization($addAffiliatedCenterRequest);

            $existingCenterCodeArray = $this->persistenceInterface->getAllUsersCenters($addAffiliatedCenterRequest->userId);

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
                throw new GaelOConflictException('Center already affiliated to user');
            }

        } catch(GaelOException $e) {
            $addAffiliatedCenterResponse->status = $e->statusCode;
            $addAffiliatedCenterResponse->statusText =$e->statusText;
            $addAffiliatedCenterResponse->body =$e->getErrorBody();
        } catch (Exception $e){
            throw $e;
        };

    }

    private function checkAuthorization(AddAffiliatedCenterRequest $addAffiliatedCenterRequest){
        $this->authorizationService->setCurrentUser($addAffiliatedCenterRequest->currentUserId);
        if( ! $this->authorizationService->isAdmin()){
            throw new GaelOForbiddenException();
        };
    }
}
