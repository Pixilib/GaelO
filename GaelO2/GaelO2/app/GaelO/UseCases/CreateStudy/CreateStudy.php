<?php

namespace App\GaelO\UseCases\CreateStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;


class CreateStudy {

    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface){
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(CreateStudyRequest $createStudyRequest, CreateStudyResponse $createStudyResponse){

        try{
            $this->checkAuthorization($createStudyRequest->currentUserId);

            $studyName = $createStudyRequest->name;
            $studyCode = $createStudyRequest->code;
            $patientCodeLength = $createStudyRequest->patientCodeLength;
            $contactEmail = $createStudyRequest->contactEmail;
            $ancillaryOf = $createStudyRequest->ancillaryOf;

            if(preg_match('/[^A-Z0-9]/', $studyName)){
                throw new GaelOBadRequestException('Only uppercase alphanumerical name allowed, no space or special characters');
            }

            if( $this->studyRepositoryInterface->isExistingStudy($studyName) ){
                throw new GaelOConflictException('Already Existing Study');
            }

            if( $this->studyRepositoryInterface->isExistingCode($studyCode) ){
                throw new GaelOConflictException('Already used study code');
            }

            if( empty($patientCodeLength) ){
                throw new GaelOBadRequestException('Missing Patient Code Lenght');
            }

            if( empty($contactEmail) ){
                throw new GaelOBadRequestException('Missing Contact Email');
            }

            $this->studyRepositoryInterface->addStudy($studyName, $studyCode, $patientCodeLength, $contactEmail, $ancillaryOf);

            $currentUserId=$createStudyRequest->currentUserId;
            $actionDetails = [
                'studyName' => $studyName,
                'studyCode' => $studyCode,
                'ancillaryOf' => $ancillaryOf
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
        $this->authorizationUserService->setUserId($currentUserId);
        if( ! $this->authorizationUserService->isAdmin($currentUserId) ) {
            throw new GaelOForbiddenException();
        };
    }

}
