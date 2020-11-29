<?php

namespace App\GaelO\UseCases\CreateVisitType;

use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class CreateVisitType {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService) {
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute( CreateVisitTypeRequest $createVisitTypeRequest, CreateVisitTypeResponse $createVisitTypeResponse ){

        try{
            $this->checkAuthorization($createVisitTypeRequest->currentUserId);

            if(  $this->persistenceInterface->isExistingVisitType($createVisitTypeRequest->visitGroupId, $createVisitTypeRequest->name)){
                throw new GaelOConflictException('Already Existing Visit Name in group');
            }

            //SK VERIFIER QUIL N Y A PAS DE VISITE CREE dans la study

            $this->persistenceInterface->createVisitType(
                $createVisitTypeRequest->visitGroupId,
                $createVisitTypeRequest->name,
                $createVisitTypeRequest->visitOrder,
                $createVisitTypeRequest->localFormNeeded,
                $createVisitTypeRequest->qcNeeded,
                $createVisitTypeRequest->reviewNeeded,
                $createVisitTypeRequest->optional,
                $createVisitTypeRequest->limitLowDays,
                $createVisitTypeRequest->limitUpDays,
                $createVisitTypeRequest->anonProfile
            );

            $createVisitTypeResponse->status = 201;
            $createVisitTypeResponse->statusText = 'Created';

        } catch (GaelOException $e){

            $createVisitTypeResponse->body = $e->getErrorBody();
            $createVisitTypeResponse->status = $e->statusCode;
            $createVisitTypeResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }


    private function checkAuthorization(int $userId){
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin()){
            throw new GaelOForbiddenException();
        }


    }


}
