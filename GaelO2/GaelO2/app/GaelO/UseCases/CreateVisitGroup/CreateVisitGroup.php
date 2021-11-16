<?php

namespace App\GaelO\UseCases\CreateVisitGroup;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitGroupRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class CreateVisitGroup {

    private VisitGroupRepositoryInterface $visitGroupRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(VisitGroupRepositoryInterface $visitGroupRepositoryInterface, VisitRepositoryInterface $visitRepositoryInterface, AuthorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface){

        $this->visitGroupRepositoryInterface = $visitGroupRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
        $this->visitRepositoryInterface =$visitRepositoryInterface;

    }

    public function execute(CreateVisitGroupRequest $createVisitGroupRequest, CreateVisitGroupResponse $createVisitGroupResponse) : void {

        try{
            $this->checkAuthorization($createVisitGroupRequest->currentUserId);

            $existingVisitGroup = $this->visitGroupRepositoryInterface->isExistingVisitGroup($createVisitGroupRequest->studyName,
                                                            $createVisitGroupRequest->name);

            if($existingVisitGroup) {
                throw new GaelOConflictException("Already Exisiting Visit Group");
            }

            $hasVisits = $this->visitRepositoryInterface->hasVisitsInStudy($createVisitGroupRequest->studyName);

            if($hasVisits) {
                throw new GaelOForbiddenException("Study already having visits, can't change workflow");
            }

            $this->visitGroupRepositoryInterface->createVisitGroup($createVisitGroupRequest->studyName, $createVisitGroupRequest->name, $createVisitGroupRequest->modality);

            $actionDetails = [
                'modality' => $createVisitGroupRequest->modality
            ];

            $this->trackerRepositoryInterface->writeAction($createVisitGroupRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, $createVisitGroupRequest->studyName, null, Constants::TRACKER_CREATE_VISIT_GROUP, $actionDetails);
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

    private function checkAuthorization(int $userId){
        $this->authorizationUserService->setUserId($userId);
        if(! $this->authorizationUserService->isAdmin() ){
            throw new GaelOForbiddenException();
        }
    }


}
