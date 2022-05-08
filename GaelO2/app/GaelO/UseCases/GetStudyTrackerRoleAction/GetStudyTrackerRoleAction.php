<?php

namespace App\GaelO\UseCases\GetStudyTrackerRoleAction;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Entities\TrackerEntity;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetStudyTrackerRoleAction {

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(TrackerRepositoryInterface $trackerRepositoryInterface, AuthorizationStudyService $authorizationStudyService){
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(GetStudyTrackerRoleActionRequest $getStudyTrackerRoleActionRequest, GetStudyTrackerRoleActionResponse $getStudyTrackerRoleActionResponse) : void {

        try{

            $this->checkAuthorization($getStudyTrackerRoleActionRequest->currentUserId, $getStudyTrackerRoleActionRequest->studyName, $getStudyTrackerRoleActionRequest->role);

            $dbData = $this->trackerRepositoryInterface->getTrackerOfRoleActionInStudy($getStudyTrackerRoleActionRequest->role, $getStudyTrackerRoleActionRequest->actionType, $getStudyTrackerRoleActionRequest->studyName);

            $responseArray = [];
            foreach($dbData as $data){
                $trackerEntity = TrackerEntity::fillFromDBReponseArray($data);
                $trackerEntity->setUserData($data['user']);
                $trackerEntity->setVisitData($data['visit']);
                $responseArray[] = $trackerEntity;
            }

            $getStudyTrackerRoleActionResponse->body = $responseArray;
            $getStudyTrackerRoleActionResponse->status = 200;
            $getStudyTrackerRoleActionResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $getStudyTrackerRoleActionResponse->body = $e->getErrorBody();
            $getStudyTrackerRoleActionResponse->status = $e->statusCode;
            $getStudyTrackerRoleActionResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, string $studyName){
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if ( ! $this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)){
            throw new GaelOForbiddenException();
        }
    }
}
