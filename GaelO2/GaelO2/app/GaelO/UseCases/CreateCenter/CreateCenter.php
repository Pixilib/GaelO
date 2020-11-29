<?php

namespace App\GaelO\UseCases\CreateCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;
use Exception;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOForbiddenException;

class CreateCenter {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService){

        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
        $this->authorizationService = $authorizationService;

    }

    public function execute(CreateCenterRequest $createCenterRequest, CreateCenterResponse $createCenterResponse){

        try{
            $this->checkAuthorization($createCenterRequest->currentUserId);

            $code = $createCenterRequest->code;
            $name = $createCenterRequest->name;
            $countryCode = $createCenterRequest->countryCode;

            if($this->persistenceInterface->isKnownCenter($code)){
                throw new GaelOConflictException("Center Code already used");
            };

            if(!empty($this->persistenceInterface->getCenterByName($createCenterRequest->name))){
                throw new GaelOConflictException("Center Name already used.");
            };

            $this->persistenceInterface->createCenter($code, $name, $countryCode);

            $actionDetails = [
                'createdCenterCode'=>$code,
                'createdCenterName'=>$name,
                'createdCenterCountryCode'=>$countryCode
            ];

            $this->trackerService->writeAction($createCenterRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_CENTER, $actionDetails);

            $createCenterResponse->status = 201;
            $createCenterResponse->statusText = 'Created';

        }catch (GaelOException $e){
            $createCenterResponse->body = $e->getErrorBody();
            $createCenterResponse->status = $e->statusCode;
            $createCenterResponse->statusText =  $e->statusText;
        }catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $currentUserId){
        $this->authorizationService->setCurrentUserAndRole($currentUserId);
        if( ! $this->authorizationService->isAdmin() ) {
            throw new GaelOForbiddenException();
        };
    }

}
