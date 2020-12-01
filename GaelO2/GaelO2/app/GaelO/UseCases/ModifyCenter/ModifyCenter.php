<?php

namespace App\GaelO\UseCases\ModifyCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Exceptions\GaelONotFoundException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\ModifyCenter\ModifyCenterRequest;
use App\GaelO\UseCases\ModifyCenter\ModifyCenterResponse;
use App\GaelO\Services\TrackerService;
use App\GaelO\Util;
use Exception;

class ModifyCenter {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
        $this->authorizationService = $authorizationService;
     }


     public function execute(ModifyCenterRequest $modifyCenterRequest, ModifyCenterResponse $modifyCenterResponse) : void
    {
        try{

            $this->checkAuthorization($modifyCenterRequest->currentUserId);

            if(!$this->persistenceInterface->isKnownCenter($modifyCenterRequest->code)){
                throw new GaelONotFoundException('Non Existing Center');
            };

            //If center name has been changed, check that name isn't already used
            if(!empty($modifyCenterRequest->name) && !empty($this->persistenceInterface->getCenterByName($modifyCenterRequest->name))){
                throw new GaelOConflictException('Center Name already used');
            };

            //Fill missing fields with known info from the database
            $center = $this->persistenceInterface->find($modifyCenterRequest->code);
            if(empty($modifyCenterRequest->name)) $modifyCenterRequest->name = $center->name;
            if(empty($modifyCenterRequest->countryCode)) $modifyCenterRequest->countryCode = $center->country_code;

            $this->persistenceInterface->updateCenter($modifyCenterRequest->name, $modifyCenterRequest->code, $modifyCenterRequest->countryCode);

            $actionDetails = [
                'modifiedCenter' => $modifyCenterRequest->code,
                'centerName'=> $modifyCenterRequest->name,
                'centerCountryCode' =>  $modifyCenterRequest->countryCode,
            ];

            $this->trackerService->writeAction($modifyCenterRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_CENTER, $actionDetails);

            $modifyCenterResponse->status = 200;
            $modifyCenterResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $modifyCenterResponse->status = $e->statusCode;
            $modifyCenterResponse->statusText = $e->statusText;
            $modifyCenterResponse->body = $e->getErrorBody();

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

?>
