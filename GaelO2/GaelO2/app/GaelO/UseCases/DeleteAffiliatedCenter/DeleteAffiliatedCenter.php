<?php

namespace App\GaelO\UseCases\DeleteAffiliatedCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Interfaces\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class DeleteAffiliatedCenter {

    private UserRepositoryInterface $userRepositoryInterface;
    private AuthorizationService $authorizationService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationService $authorizationService, TrackerRepositoryInterface $trackerRepositoryInterface) {
        $this->userRepositoryInterface=$userRepositoryInterface;
        $this->trackerRepositoryInterface=$trackerRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(DeleteAffiliatedCenterRequest $deleteAffiliatedCenterRequest, DeleteAffiliatedCenterResponse $deleteAffiliatedCenterResponse){

        try{

            $this->checkAuthorization($deleteAffiliatedCenterRequest->currentUserId);

            $this->userRepositoryInterface->deleteAffiliatedCenter($deleteAffiliatedCenterRequest->userId, $deleteAffiliatedCenterRequest->centerCode);

            $actionDetails = [
                'deletedAffiliatedCenters' => $deleteAffiliatedCenterRequest->centerCode
            ];

            $this->trackerRepositoryInterface->writeAction($deleteAffiliatedCenterRequest->userId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_USER, $actionDetails);

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
