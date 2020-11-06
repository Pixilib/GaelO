<?php

namespace App\GaelO\UseCases\CreateVisitGroup;

use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;
use Exception;

class CreateVisitGroup {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){

        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;

    }

    public function execute(CreateVisitGroupRequest $createVisitGroupRequest, CreateVisitGroupResponse $createVisitGroupResponse) : void {

        try{
            $this->checkAuthorization($createVisitGroupRequest);

            $existingVisitGroup = $this->persistenceInterface->isExistingVisitGroup($createVisitGroupRequest->studyName,
                                                            $createVisitGroupRequest->modality);

            if($existingVisitGroup) {
                throw new GaelOConflictException("Already Exisiting Visit Group");
            }

            $this->persistenceInterface->createVisitGroup($createVisitGroupRequest->studyName, $createVisitGroupRequest->modality);

            $createVisitGroupResponse->status = 201;
            $createVisitGroupResponse->statusText = 'Created';

        } catch (GaelOException $e) {

            $createVisitGroupResponse->status = $e->statusCode;
            $createVisitGroupResponse->statusText = $e->statusText;
            $createVisitGroupResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(CreateVisitGroupRequest $createVisitGroupRequest){
        $this->authorizationService->setCurrentUser($createVisitGroupRequest->currentUserId);
        $answer = $this->authorizationService->isAdmin();
        if(!$answer) throw new GaelOForbiddenException();

    }


}
