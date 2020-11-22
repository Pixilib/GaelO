<?php

namespace App\GaelO\UseCases\CreateVisit;

use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;
use App\GaelO\Services\VisitService;

class CreateVisit {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService, VisitService $visitService){
        $this->visitService = $visitService;
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
        $this->authorizationService = $authorizationService;

    }

    public function execute(CreateVisitRequest $createVisitRequest, CreateVisitResponse $createVisitResponse) : void {

        try{

            $this->checkAuthorization($createVisitRequest->currentUserId, $createVisitRequest->role, $createVisitRequest->patientCode);

            $existingVisit = $this->persistenceInterface->isExistingVisit($createVisitRequest->patientCode,
                                                            $createVisitRequest->visitTypeId);


            if($existingVisit) {
                throw new GaelOConflictException('Visit Already Created');
            }else{

                $this->visitService->createVisit(
                    $createVisitRequest->studyName,
                    $createVisitRequest->creatorUserId,
                    $createVisitRequest->patientCode,
                    $createVisitRequest->acquisitionDate,
                    $createVisitRequest->visitTypeId,
                    $createVisitRequest->statusDone,
                    $createVisitRequest->reasonForNotDone);

                $createVisitResponse->status = 201;
                $createVisitResponse->statusText = 'Created';
            }
        } catch (GaelOException $e){
            $createVisitResponse->status = $e->statusCode;
            $createVisitResponse->statusText = $e->statusText;
            $createVisitResponse->body = $e->getErrorBody();
        }


    }

    private function checkAuthorization(int $userId, string $role, int $patientCode) : void{
        $this->authorizationService->setCurrentUserAndRole($userId, $role);
        $this->authorizationService->setPatient($patientCode);
        if (! $this->authorizationService->isPatientAllowed() ){
            throw new GaelOForbiddenException();
        }


    }


}
