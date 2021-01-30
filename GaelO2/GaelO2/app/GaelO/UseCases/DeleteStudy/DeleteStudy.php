<?php

namespace App\GaelO\UseCases\DeleteStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\StudyRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class DeleteStudy {

    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationService $authorizationService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationService $authorizationService, TrackerRepositoryInterface $trackerRepositoryInterface) {
        $this->persistenceInterface=$studyRepositoryInterface;
        $this->trackerRepositoryInterface=$trackerRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(DeleteStudyRequest $deleteStudyRequest, DeleteStudyResponse $deleteStudyResponse){

        try{

            $this->checkAuthorization($deleteStudyRequest->currentUserId);
            $studyName = $deleteStudyRequest->studyName;
            $this->studyRepositoryInterface->delete($studyName);

            $this->trackerRepositoryInterface->writeAction($deleteStudyRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, $studyName, null, Constants::TRACKER_DEACTIVATE_STUDY, []);

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
