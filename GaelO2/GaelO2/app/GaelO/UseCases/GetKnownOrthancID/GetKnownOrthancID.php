<?php

namespace App\GaelO\UseCases\GetKnownOrthancID;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetKnownOrthancID{

    public function __construct( PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService)
    {
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetKnownOrthancIDRequest $getKnownOrthancIDRequest, GetKnownOrthancIDResponse $getKnownOrthancIDResponse){
        try{

            $this->checkAuthorization($getKnownOrthancIDRequest->currentUserId, $getKnownOrthancIDRequest->studyName);

            $known = $this->persistenceInterface->isExistingOrthancStudyID($getKnownOrthancIDRequest->orthancStudyID);

            if($known){
                $getKnownOrthancIDResponse->body = $known;
                $getKnownOrthancIDResponse->status = '200';
                $getKnownOrthancIDResponse->statusText = 'OK';
            } else {
                $getKnownOrthancIDResponse->body = ['errorMessage'=>'Unknown Orthanc Study ID'];
                $getKnownOrthancIDResponse->status = '404';
                $getKnownOrthancIDResponse->statusText = 'Not Found';
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
