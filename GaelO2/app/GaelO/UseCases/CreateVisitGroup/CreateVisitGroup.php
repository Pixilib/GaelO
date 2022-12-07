<?php

namespace App\GaelO\UseCases\CreateVisitGroup;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitGroupRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class CreateVisitGroup
{
    private StudyRepositoryInterface $studyRepositoryInterface;
    private VisitGroupRepositoryInterface $visitGroupRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(
        StudyRepositoryInterface $studyRepositoryInterface,
        VisitGroupRepositoryInterface $visitGroupRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        AuthorizationUserService $authorizationUserService,
        TrackerRepositoryInterface $trackerRepositoryInterface
        )
    {
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->visitGroupRepositoryInterface = $visitGroupRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function execute(CreateVisitGroupRequest $createVisitGroupRequest, CreateVisitGroupResponse $createVisitGroupResponse): void
    {

        try {
            $currentUserId = $createVisitGroupRequest->currentUserId;
            $studyName = $createVisitGroupRequest->studyName;
            $visitGroupName = $createVisitGroupRequest->name;
            $visitGroupModality = $createVisitGroupRequest->modality;

            $studyEntity = $this->studyRepositoryInterface->find($studyName);
            if($studyEntity->isAncillaryStudy()){
                throw new GaelOForbiddenException("Forbidden for ancillary study");
            }

            $this->checkAuthorization($currentUserId);

            $existingVisitGroup = $this->visitGroupRepositoryInterface->isExistingVisitGroup(
                $studyName,
                $visitGroupName
            );

            if ($existingVisitGroup) throw new GaelOConflictException("Already Exisiting Visit Group");

            $hasVisits = $this->visitRepositoryInterface->hasVisitsInStudy($studyName);

            if ($hasVisits) throw new GaelOForbiddenException("Study already having visits, can't change workflow");

            $this->visitGroupRepositoryInterface->createVisitGroup($studyName, $visitGroupName, $visitGroupModality);

            $actionDetails = [
                'modality' => $visitGroupModality,
                'name' => $visitGroupName
            ];

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, $studyName, null, Constants::TRACKER_CREATE_VISIT_GROUP, $actionDetails);
            $createVisitGroupResponse->status = 201;
            $createVisitGroupResponse->statusText = 'Created';

        } catch (AbstractGaelOException $e) {
            $createVisitGroupResponse->status = $e->statusCode;
            $createVisitGroupResponse->statusText = $e->statusText;
            $createVisitGroupResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId)
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        }
    }
}
