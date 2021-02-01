<?php

namespace App\GaelO\UseCases\GetTracker;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetTracker {

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(TrackerRepositoryInterface $trackerRepositoryInterface, AuthorizationService $authorizationService){
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    //SK CETTE CLASSE EVOLUERA AVEC SUPERVISOR POUR L INSTANT ACCESSIBLE QUE PAR ADMIN, PROBABLEMENT QUERY TAG OU URI A REVOIR
    public function execute(GetTrackerRequest $getTrackerRequest, GetTrackerResponse $getTrackerResponse) : void {

        try{

            $this->checkAuthorization($getTrackerRequest->currentUserId);

            $admin = $getTrackerRequest->admin;
            if (filter_var($admin, FILTER_VALIDATE_BOOLEAN)) $dbData = $this->trackerRepositoryInterface->getTrackerOfRole(Constants::TRACKER_ROLE_ADMINISTRATOR);
            else $dbData = $this->trackerRepositoryInterface->getTrackerOfRole(Constants::TRACKER_ROLE_USER);

            $responseArray = [];
            foreach($dbData as $data){
                $trackerEntity = TrackerEntity::fillFromDBReponseArray($data);
                $trackerEntity->setUserData($data['user']);
                $responseArray[] = $trackerEntity;
            }

            $getTrackerResponse->body = $responseArray;
            $getTrackerResponse->status = 200;
            $getTrackerResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $getTrackerResponse->body = $e->getErrorBody();
            $getTrackerResponse->status = $e->statusCode;
            $getTrackerResponse->statusText = $e->statusText;

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
