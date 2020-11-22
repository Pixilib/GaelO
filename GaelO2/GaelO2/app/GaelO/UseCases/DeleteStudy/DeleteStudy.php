<?php

namespace App\GaelO\UseCases\DeleteStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;
use Exception;

class DeleteStudy {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService) {
        $this->persistenceInterface=$persistenceInterface;
        $this->trackerService=$trackerService;
        $this->authorizationService = $authorizationService;
    }

    public function execute(DeleteStudyRequest $deleteStudyRequest, DeleteStudyResponse $deleteStudyResponse){

        try{

            $this->checkAuthorization($deleteStudyRequest->currentUserId);
            $studyName = $deleteStudyRequest->studyName;
            $this->persistenceInterface->delete($studyName);

            $this->trackerService->writeAction($deleteStudyRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, $studyName, null, Constants::TRACKER_DEACTIVATE_STUDY, []);

            $deleteStudyResponse->status = 200;
            $deleteStudyResponse->statusText = 'OK';

        } catch(GaelOException $e){
            $deleteStudyResponse->status = $e->statusCode;
            $deleteStudyResponse->statusText = $e->statusText;

        } catch(Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $userId){
        $this->authorizationService->setCurrentUserAndRole($userId);
        if ( ! $this->authorizationService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }

}
