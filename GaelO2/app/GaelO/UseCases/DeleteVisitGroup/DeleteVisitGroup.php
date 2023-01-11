<?php

namespace App\GaelO\UseCases\DeleteVisitGroup;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitGroupRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class DeleteVisitGroup
{

    private StudyRepositoryInterface $studyRepositoryInterface;
    private VisitGroupRepositoryInterface $visitGroupRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(
        StudyRepositoryInterface $studyRepositoryInterface,
        VisitGroupRepositoryInterface $visitGroupRepositoryInterface,
        AuthorizationUserService $authorizationUserService
    ) {
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->visitGroupRepositoryInterface = $visitGroupRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(DeleteVisitGroupRequest $deleteVisitGroupRequest, DeleteVisitGroupResponse $deleteVisitGroupResponse)
    {

        try {

            $currentUserId = $deleteVisitGroupRequest->currentUserId;
            $visitGroupId = $deleteVisitGroupRequest->visitGroupId;

            $this->checkAuthorization($currentUserId);
            $visitGroupEntity = $this->visitGroupRepositoryInterface->find($visitGroupId);
            $studyName = $visitGroupEntity['study_name'];
            $studyNameEntity = $this->studyRepositoryInterface->find($studyName);
            if ($studyNameEntity->isAncillaryStudy()) {
                throw new GaelOForbiddenException("Forbidden for ancillary studies");
            }
            $hasVisitTypes = $this->visitGroupRepositoryInterface->hasVisitTypes($visitGroupId);

            if ($hasVisitTypes) throw new GaelOForbiddenException('Existing Child Visit Type');

            $this->visitGroupRepositoryInterface->delete($visitGroupId);

            $deleteVisitGroupResponse->status = 200;
            $deleteVisitGroupResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $deleteVisitGroupResponse->body = $e->getErrorBody();
            $deleteVisitGroupResponse->status = $e->statusCode;
            $deleteVisitGroupResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function checkAuthorization(int $userId): void
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        }
    }
}
