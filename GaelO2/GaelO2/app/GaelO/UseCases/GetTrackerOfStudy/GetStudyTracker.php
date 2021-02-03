<?php

namespace App\GaelO\UseCases\getStudyTracker;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class getStudyTracker {

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(TrackerRepositoryInterface $trackerRepositoryInterface, AuthorizationService $authorizationService){
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(getStudyTrackerRequest $getStudyTrackerRequest, getStudyTrackerResponse $getStudyTrackerResponse) : void {

        try{

            $this->checkAuthorization($getStudyTrackerRequest->currentUserId);

            $requiredTracker = $getStudyTrackerRequest->requiredTracker;
            $dbData = $this->trackerRepositoryInterface->getStudyTrackerOfRole(strtoupper($requiredTracker));

            $responseArray = [];
            foreach($dbData as $data){
                $trackerEntity = TrackerEntity::fillFromDBReponseArray($data);
                $trackerEntity->setUserData($data['user']);
                $responseArray[] = $trackerEntity;
            }

            $getStudyTrackerResponse->body = $responseArray;
            $getStudyTrackerResponse->status = 200;
            $getStudyTrackerResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $getStudyTrackerResponse->body = $e->getErrorBody();
            $getStudyTrackerResponse->status = $e->statusCode;
            $getStudyTrackerResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $userId) : void  {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }
}
