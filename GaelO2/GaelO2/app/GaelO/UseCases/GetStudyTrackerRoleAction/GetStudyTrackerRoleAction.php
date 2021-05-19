<?php

namespace App\GaelO\UseCases\GetStudyTrackerRoleAction;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Entities\TrackerEntity;
use Exception;

class GetStudyTrackerRoleAction {

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(TrackerRepositoryInterface $trackerRepositoryInterface, AuthorizationService $authorizationService){
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetStudyTrackerRoleActionRequest $GetStudyTrackerRoleActionRequest, GetStudyTrackerRoleActionResponse $GetStudyTrackerRoleActionResponse) : void {

        try{

            $this->checkAuthorization($GetStudyTrackerRoleActionRequest->currentUserId, $GetStudyTrackerRoleActionRequest->studyName, $GetStudyTrackerRoleActionRequest->role);

            $dbData = $this->trackerRepositoryInterface->getTrackerOfRoleActionInStudy($GetStudyTrackerRoleActionRequest->trackerOfRole, $GetStudyTrackerRoleActionRequest->actionType, $GetStudyTrackerRoleActionRequest->studyName);

            $responseArray = [];
            foreach($dbData as $data){
                $trackerEntity = TrackerEntity::fillFromDBReponseArray($data);
                $trackerEntity->setUserData($data['user']);
                $responseArray[] = $trackerEntity;
            }

            $GetStudyTrackerRoleActionResponse->body = $responseArray;
            $GetStudyTrackerRoleActionResponse->status = 200;
            $GetStudyTrackerRoleActionResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $GetStudyTrackerRoleActionResponse->body = $e->getErrorBody();
            $GetStudyTrackerRoleActionResponse->status = $e->statusCode;
            $GetStudyTrackerRoleActionResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, string $studyName, string $role){
        $this->authorizationService->setCurrentUserAndRole($currentUserId, $role);
        if ( ! $this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        }
    }
}
