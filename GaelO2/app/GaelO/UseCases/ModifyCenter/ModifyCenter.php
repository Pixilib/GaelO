<?php

namespace App\GaelO\UseCases\ModifyCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Exceptions\GaelONotFoundException;
use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use App\GaelO\UseCases\ModifyCenter\ModifyCenterRequest;
use App\GaelO\UseCases\ModifyCenter\ModifyCenterResponse;
use Exception;

class ModifyCenter {

    private CenterRepositoryInterface $centerRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(CenterRepositoryInterface $centerRepositoryInterface, AuthorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface){
        $this->centerRepositoryInterface = $centerRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
     }


     public function execute(ModifyCenterRequest $modifyCenterRequest, ModifyCenterResponse $modifyCenterResponse) : void
    {
        try{

            $this->checkAuthorization($modifyCenterRequest->currentUserId);

            if(!$this->centerRepositoryInterface->isKnownCenter($modifyCenterRequest->code)){
                throw new GaelONotFoundException('Non Existing Center');
            };

            //If center name has been changed, check that name isn't already used
            if(!empty($modifyCenterRequest->name) && $this->centerRepositoryInterface->isExistingCenterName($modifyCenterRequest->name) ){
                throw new GaelOConflictException('Center Name already used');
            };

            //Fill missing fields with known info from the database
            $center = $this->centerRepositoryInterface->getCenterByCode($modifyCenterRequest->code);
            if(!empty($modifyCenterRequest->name)) $center['name'] = $modifyCenterRequest->name;
            if(!empty($modifyCenterRequest->countryCode)) $center['country_code'] = $modifyCenterRequest->countryCode;

            $this->centerRepositoryInterface->updateCenter($center['code'], $center['name'], $center['country_code']);

            $actionDetails = $center;

            $this->trackerRepositoryInterface->writeAction($modifyCenterRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_CENTER, $actionDetails);

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
        $this->authorizationUserService->setUserId($userId);
        if( ! $this->authorizationUserService->isAdmin() ) {
            throw new GaelOForbiddenException();
        };
    }


}
