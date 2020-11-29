<?php

namespace App\GaelO\UseCases\GetAffiliatedCenter;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetAffiliatedCenter {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetAffiliatedCenterRequest $getAffiliatedCenterRequest, GetAffiliatedCenterResponse $getAffiliatedCenterResponse){

        try{

            $this->checkAuthorization($getAffiliatedCenterRequest->currentUserId);
            $affiliatedCenters = $this->persistenceInterface->getAffiliatedCenter($getAffiliatedCenterRequest->userId);
            $centerResponseArray = [];

            foreach($affiliatedCenters as $center){
                $centerResponseArray[]  = CenterEntity::fillFromDBReponseArray($center);
            }

            $getAffiliatedCenterResponse->body =  $centerResponseArray;
            $getAffiliatedCenterResponse->status = 200;
            $getAffiliatedCenterResponse->statusText = 'OK';

        } catch (GaelOException $e) {
            $getAffiliatedCenterResponse->body =  $e->getErrorBody();
            $getAffiliatedCenterResponse->status = $e->statusCode;
            $getAffiliatedCenterResponse->statusText = $e->statusCode;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $userId) : void {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }


}
