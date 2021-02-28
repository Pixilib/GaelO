<?php

namespace App\GaelO\UseCases\GetKnownOrthancID;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Exceptions\GaelONotFoundException;
use App\GaelO\Interfaces\DicomStudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;
use Illuminate\Support\Facades\Log;

class GetKnownOrthancID{

    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct( DicomStudyRepositoryInterface $dicomStudyRepositoryInterface, AuthorizationService $authorizationService)
    {
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetKnownOrthancIDRequest $getKnownOrthancIDRequest, GetKnownOrthancIDResponse $getKnownOrthancIDResponse){
        try{

            $this->checkAuthorization($getKnownOrthancIDRequest->currentUserId, $getKnownOrthancIDRequest->studyName);

            $known = $this->dicomStudyRepositoryInterface->isExistingOriginalOrthancStudyID($getKnownOrthancIDRequest->orthancStudyID, $getKnownOrthancIDRequest->studyName);

            if($known){
                $getKnownOrthancIDResponse->body = $known;
                $getKnownOrthancIDResponse->status = '200';
                $getKnownOrthancIDResponse->statusText = 'OK';
            } else {
                throw new GaelONotFoundException('Unknown Orthanc Study ID');
            }


        } catch(GaelOException $e){

            $getKnownOrthancIDResponse->body = $e->getErrorBody();
            $getKnownOrthancIDResponse->status = $e->statusCode;
            $getKnownOrthancIDResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, string $studyName){
        $this->authorizationService->setCurrentUserAndRole($currentUserId, Constants::ROLE_INVESTIGATOR);
        if( ! $this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        };

    }
}
