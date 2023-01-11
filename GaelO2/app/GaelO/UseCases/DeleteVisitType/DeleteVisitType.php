<?php

namespace App\GaelO\UseCases\DeleteVisitType;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitTypeRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class DeleteVisitType
{
    private StudyRepositoryInterface $studyRepositoryInterface;
    private VisitTypeRepositoryInterface $visitTypeRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private VisitRepositoryInterface $visitRepositoryInterface;

    public function __construct(
        StudyRepositoryInterface $studyRepositoryInterface,
        VisitTypeRepositoryInterface $visitTypeRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        AuthorizationUserService $authorizationUserService
    ) {
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->visitTypeRepositoryInterface = $visitTypeRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(DeleteVisitTypeRequest $deleteVisitTypeRequest, DeleteVisitTypeResponse $deleteVisitTypeResponse)
    {

        try {
            $this->checkAuthorization($deleteVisitTypeRequest->currentUserId);

            $visitTypeEntity = $this->visitTypeRepositoryInterface->find($deleteVisitTypeRequest->visitTypeId, true);
            $studyName = $visitTypeEntity['visit_group']['study_name'];
            $studyEntity = $this->studyRepositoryInterface->find($studyName);
            
            if ($studyEntity->isAncillaryStudy()) {
                throw new GaelOForbiddenException("Forbidden for ancillary studies");
            }

            $hasVisits = $this->visitRepositoryInterface->hasVisitsInStudy($studyName);

            if ($hasVisits) throw new GaelOForbiddenException('Existing Visits in the study');

            $this->visitTypeRepositoryInterface->delete($deleteVisitTypeRequest->visitTypeId);
            $deleteVisitTypeResponse->status = 200;
            $deleteVisitTypeResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $deleteVisitTypeResponse->status = $e->statusCode;
            $deleteVisitTypeResponse->statusText = $e->statusText;
            $deleteVisitTypeResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function checkAuthorization(int $userId)
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        }
    }
}
