<?php

namespace App\GaelO\UseCases\DeleteVisitType;

use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitTypeRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class DeleteVisitType {

    private VisitTypeRepositoryInterface $visitTypeRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(VisitTypeRepositoryInterface $visitTypeRepositoryInterface, AuthorizationService $authorizationService){
        $this->visitTypeRepositoryInterface = $visitTypeRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(DeleteVisitTypeRequest $deleteVisitTypeRequest, DeleteVisitTypeResponse $deleteVisitTypeResponse){

        try{

            $this->checkAuthorization($deleteVisitTypeRequest->currentUserId);

            $hasVisits = $this->visitTypeRepositoryInterface->hasVisits($deleteVisitTypeRequest->visitTypeId);
            if($hasVisits) throw new GaelOConflictException('Existing Child Visits');

            $this->visitTypeRepositoryInterface->delete($deleteVisitTypeRequest->visitTypeId);
            $deleteVisitTypeResponse->status = 200;
            $deleteVisitTypeResponse->statusText = 'OK';

        }catch(GaelOException $e){
            $deleteVisitTypeResponse->status = $e->statusCode;
            $deleteVisitTypeResponse->statusText = $e->statusText;
            $deleteVisitTypeResponse->body = $e->getErrorBody();

        }catch (Exception $e){
            throw $e;
        }


    }

    public function checkAuthorization(int $userId){
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }

}
