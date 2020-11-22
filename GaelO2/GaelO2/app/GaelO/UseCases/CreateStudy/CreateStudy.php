<?php

namespace App\GaelO\UseCases\CreateStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use Exception;


class CreateStudy {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
        $this->trackerService = $trackerService;
    }

    public function execute(CreateStudyRequest $createStudyRequest, CreateStudyResponse $createStudyResponse){

        try{
            $this->checkAuthorization($createStudyRequest->currentUserId);

            $studyName = $createStudyRequest->studyName;
            $patientCodePrefix = $createStudyRequest->patientCodePrefix;

            if( $this->persistenceInterface->isExistingStudy($studyName) ){
                throw new GaelOConflictException('Already Existing Study');
            }

            $this->persistenceInterface->addStudy($studyName, $patientCodePrefix);

            $currentUserId=$createStudyRequest->currentUserId;
            $actionDetails = [
                'studyName'=>$studyName,
                'patientCodePrefix'=> $patientCodePrefix
            ];

            $this->trackerService->writeAction($currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_CREATE_STUDY, $actionDetails);

            $createStudyResponse->status = 201;
            $createStudyResponse->statusText = 'Created';

        }catch(GaelOException $e){
            $createStudyResponse->body = $e->getErrorBody();
            $createStudyResponse->status = $e->statusCode;
            $createStudyResponse->statusText = $e->statusText;
        }catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId){
        $this->authorizationService->setCurrentUserAndRole($currentUserId);
        if( ! $this->authorizationService->isAdmin($currentUserId) ) {
            throw new GaelOForbiddenException();
        };
    }

}
