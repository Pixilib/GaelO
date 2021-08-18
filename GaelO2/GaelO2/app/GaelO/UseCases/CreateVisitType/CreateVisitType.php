<?php

namespace App\GaelO\UseCases\CreateVisitType;

use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitGroupRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitTypeRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class CreateVisitType {

    private VisitTypeRepositoryInterface $visitTypeRepositoryInterface;
    private VisitGroupRepositoryInterface $visitGroupRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(VisitTypeRepositoryInterface $visitTypeRepositoryInterface, VisitGroupRepositoryInterface $visitGroupRepositoryInterface, VisitRepositoryInterface $visitRepositoryInterface, AuthorizationService $authorizationService) {
        $this->visitGroupRepositoryInterface = $visitGroupRepositoryInterface;
        $this->visitTypeRepositoryInterface = $visitTypeRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute( CreateVisitTypeRequest $createVisitTypeRequest, CreateVisitTypeResponse $createVisitTypeResponse ){

        try{
            $this->checkAuthorization($createVisitTypeRequest->currentUserId);

            if(  $this->visitTypeRepositoryInterface->isExistingVisitType($createVisitTypeRequest->visitGroupId, $createVisitTypeRequest->name)){
                throw new GaelOConflictException('Already Existing Visit Name in group');
            }

            $visitGroup = $this->visitGroupRepositoryInterface->find($createVisitTypeRequest->visitGroupId);
            $hasVisits = $this->visitRepositoryInterface->hasVisitsInStudy($visitGroup['study_name']);

            if($hasVisits) {
                throw new GaelOForbiddenException("Study already having visits, can't change workflow");
            }

            $this->visitTypeRepositoryInterface->createVisitType(
                $createVisitTypeRequest->visitGroupId,
                $createVisitTypeRequest->name,
                $createVisitTypeRequest->order,
                $createVisitTypeRequest->localFormNeeded,
                $createVisitTypeRequest->qcNeeded,
                $createVisitTypeRequest->reviewNeeded,
                $createVisitTypeRequest->optional,
                $createVisitTypeRequest->limitLowDays,
                $createVisitTypeRequest->limitUpDays,
                $createVisitTypeRequest->anonProfile,
                $createVisitTypeRequest->dicomConstraints
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
