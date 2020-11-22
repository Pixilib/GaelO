<?php

namespace App\GaelO\UseCases\ReactivateStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;
use Exception;

class ReactivateStudy {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
        $this->authorizationService = $authorizationService;
    }

    public function execute(ReactivateStudyRequest $reactivateStudyRequest, ReactivateStudyResponse $reactivateStudyResponse){

        try {

            $this->checkAuthorization($reactivateStudyRequest->currentUserId);

            $this->persistenceInterface->reactivateStudy($reactivateStudyRequest->studyName);

            $actionsDetails = [
                'reactivatedStudy' => $reactivateStudyRequest->studyName
            ];
            $this->trackerService->writeAction($reactivateStudyRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_REACTIVATE_STUDY, $actionsDetails);

            $reactivateStudyResponse->status = 200;
            $reactivateStudyResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $reactivateStudyResponse->status = $e->statusCode;
            $reactivateStudyResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization($userId)  {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin() ) {
            throw new GaelOForbiddenException();
        };
    }

}
