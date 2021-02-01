<?php

namespace App\GaelO\UseCases\GetVisitType;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\VisitTypeRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetVisitType {

    private VisitTypeRepositoryInterface $visitTypeRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(VisitTypeRepositoryInterface $visitTypeRepositoryInterface, AuthorizationService $authorizationService){
        $this->visitTypeRepositoryInterface = $visitTypeRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetVisitTypeRequest $getVisitTypeRequest, GetVisitTypeResponse $getVisitTypeResponse){

        try{

            $this->checkAuthorization($getVisitTypeRequest->currentUserId);
            $visitType = $this->visitTypeRepositoryInterface->find($getVisitTypeRequest->visitTypeId);
            $visitTypeEntity = VisitTypeEntity::fillFromDBReponseArray($visitType);
            $getVisitTypeResponse->body = $visitTypeEntity;
            $getVisitTypeResponse->status = 200;
            $getVisitTypeResponse->statusText = 'OK';

        } catch (GaelOException $e ){

            $getVisitTypeResponse->body = $e->getErrorBody();
            $getVisitTypeResponse->status = $e->statusCode;
            $getVisitTypeResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $userId){
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin() ) {
            throw new GaelOForbiddenException();
        }

    }

}
