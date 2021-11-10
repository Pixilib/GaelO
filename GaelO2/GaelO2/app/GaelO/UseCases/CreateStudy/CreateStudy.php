<?php

namespace App\GaelO\UseCases\CreateStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use Exception;


class CreateStudy {

    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationService $authorizationService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;



    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationService $authorizationService, TrackerRepositoryInterface $trackerRepositoryInterface){
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationService = $authorizationService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(CreateStudyRequest $createStudyRequest, CreateStudyResponse $createStudyResponse){

        try{
            $this->checkAuthorization($createStudyRequest->currentUserId);

            $studyName = $createStudyRequest->name;
            $studyCode = $createStudyRequest->code;

            if(preg_match('/[^A-Z0-9]/', $studyName)){
                throw new GaelOBadRequestException('Only uppercase alfanumerical name allowed, no space or special characters');
            }

            if( $this->studyRepositoryInterface->isExistingStudy($studyName) ){
                throw new GaelOConflictException('Already Existing Study');
            }

            $this->studyRepositoryInterface->addStudy($studyName, $studyCode);

            $currentUserId=$createStudyRequest->currentUserId;
            $actionDetails = [
                'studyName' => $studyName,
                'studyCode' => $studyCode
            ];

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_CREATE_STUDY, $actionDetails);

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
