<?php

namespace App\GaelO\UseCases\GetStudyTrackerByVisit;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetTracker\TrackerEntity;
use App\GaelO\Constants\Constants;
use Exception;

class GetStudyTrackerByVisit {

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(TrackerRepositoryInterface $trackerRepositoryInterface, AuthorizationService $authorizationService){
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetStudyTrackerByVisitRequest $getStudyTrackerByVisitRequest, GetStudyTrackerByVisitResponse $getStudyTrackerByVisitResponse) : void {
        try{

            $this->checkAuthorization($getStudyTrackerByVisitRequest->currentUserId, $getStudyTrackerByVisitRequest->studyName);

            $dbData = $this->trackerRepositoryInterface->getTrackerOfVisitId($getStudyTrackerByVisitRequest->visitId);

            $responseArray = [];
            foreach($dbData as $data){
                $trackerEntity = TrackerEntity::fillFromDBReponseArray($data);
                $trackerEntity->setUserData($data['user']);
                $responseArray[] = $trackerEntity;
            }

            $getStudyTrackerByVisitResponse->body = $responseArray;
            $getStudyTrackerByVisitResponse->status = 200;
            $getStudyTrackerByVisitResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $getStudyTrackerByVisitResponse->body = $e->getErrorBody();
            $getStudyTrackerByVisitResponse->status = $e->statusCode;
            $getStudyTrackerByVisitResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $studyName){
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        if(! $this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        };
    }
}
