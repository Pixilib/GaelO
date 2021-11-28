<?php

namespace App\GaelO\UseCases\GetStudyTracker;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Entities\TrackerEntity;
use App\GaelO\Constants\Constants;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetStudyTracker {

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(TrackerRepositoryInterface $trackerRepositoryInterface, AuthorizationStudyService $authorizationStudyService){
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(GetStudyTrackerRequest $getStudyTrackerRequest, GetStudyTrackerResponse $getStudyTrackerResponse) : void {

        try{

            $this->checkAuthorization($getStudyTrackerRequest->currentUserId, $getStudyTrackerRequest->studyName, $getStudyTrackerRequest->role);

            $actionType = $getStudyTrackerRequest->actionType;
            if(in_array($actionType, [Constants::ROLE_INVESTIGATOR, Constants::ROLE_CONTROLLER,
            Constants::ROLE_SUPERVISOR, Constants::ROLE_REVIEWER]))
            $dbData = $this->trackerRepositoryInterface->getTrackerOfRoleAndStudy($getStudyTrackerRequest->studyName, $getStudyTrackerRequest->actionType, true);
            else $dbData = $this->trackerRepositoryInterface->getTrackerOfActionInStudy($getStudyTrackerRequest->actionType, $getStudyTrackerRequest->studyName);


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

    private function checkAuthorization(int $currentUserId, string $studyName, string $role){
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if ( ! $this->authorizationStudyService->isAllowedStudy($role)){
            throw new GaelOForbiddenException();
        }
    }
}
