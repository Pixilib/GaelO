<?php

namespace App\GaelO\UseCases\AddAffiliatedCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;
use Exception;

class AddAffiliatedCenter {

    private TrackerRepositoryInterface $trackerRepository;

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerRepositoryInterface $trackerRepository){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerRepository = $trackerRepository;
        $this->authorizationService = $authorizationService;
    }

    public function execute(AddAffiliatedCenterRequest $addAffiliatedCenterRequest, AddAffiliatedCenterResponse $addAffiliatedCenterResponse){

        try{
            $this->checkAuthorization($addAffiliatedCenterRequest->currentUserId);

            $existingCenterCodeArray = $this->persistenceInterface->getAllUsersCenters($addAffiliatedCenterRequest->userId);

            //Check the request creation is not in Main or affiliated centers
            if( ! in_array($addAffiliatedCenterRequest->centerCode, $existingCenterCodeArray) ){

                $this->persistenceInterface->addAffiliatedCenter($addAffiliatedCenterRequest->userId, $addAffiliatedCenterRequest->centerCode);

                $actionDetails = [
                    'addAffiliatedCenters' => $addAffiliatedCenterRequest->centerCode
                ];
                $this->trackerRepository->writeAction($addAffiliatedCenterRequest->userId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_USER, $actionDetails);

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

    private function checkAuthorization(int $userId){
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin()){
            throw new GaelOForbiddenException();
        };
    }
}
