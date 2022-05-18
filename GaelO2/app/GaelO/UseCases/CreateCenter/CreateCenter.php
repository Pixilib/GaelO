<?php

namespace App\GaelO\UseCases\CreateCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use Exception;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;

class CreateCenter {

    private AuthorizationUserService $authorizationUserService;
    private CenterRepositoryInterface $centerRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(CenterRepositoryInterface $centerRepositoryInterface, AuthorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface){

        $this->authorizationUserService = $authorizationUserService;
        $this->centerRepositoryInterface = $centerRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationService = $authorizationUserService;

    }

    public function execute(CreateCenterRequest $createCenterRequest, CreateCenterResponse $createCenterResponse){

        try{
            $this->checkAuthorization($createCenterRequest->currentUserId);

            $code = $createCenterRequest->code;
            $name = $createCenterRequest->name;
            $countryCode = $createCenterRequest->countryCode;

            if($this->centerRepositoryInterface->isKnownCenter($code)){
                throw new GaelOConflictException("Center Code already used");
            };

            if( $this->centerRepositoryInterface->isExistingCenterName($createCenterRequest->name) ){
                throw new GaelOConflictException("Center Name already used.");
            };

            $this->centerRepositoryInterface->createCenter($code, $name, $countryCode);

            $actionDetails = [
                'createdCenterCode'=>$code,
                'createdCenterName'=>$name,
                'createdCenterCountryCode'=>$countryCode
            ];

            $this->trackerRepositoryInterface->writeAction($createCenterRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_CREATE_CENTER, $actionDetails);

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
        $this->authorizationUserService->setUserId($currentUserId);
        if( ! $this->authorizationUserService->isAdmin() ) {
            throw new GaelOForbiddenException();
        };
    }

}