<?php

namespace App\GaelO\UseCases\DeleteVisitType;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitTypeRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class DeleteVisitType
{

    private VisitTypeRepositoryInterface $visitTypeRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(VisitTypeRepositoryInterface $visitTypeRepositoryInterface, AuthorizationUserService $authorizationUserService)
    {
        $this->visitTypeRepositoryInterface = $visitTypeRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(DeleteVisitTypeRequest $deleteVisitTypeRequest, DeleteVisitTypeResponse $deleteVisitTypeResponse)
    {

        try {
            $this->checkAuthorization($deleteVisitTypeRequest->currentUserId);

            $hasVisits = $this->visitTypeRepositoryInterface->hasVisits($deleteVisitTypeRequest->visitTypeId);
            if ($hasVisits) throw new GaelOForbiddenException('Existing Child Visits');

            $this->visitTypeRepositoryInterface->delete($deleteVisitTypeRequest->visitTypeId);
            $deleteVisitTypeResponse->status = 200;
            $deleteVisitTypeResponse->statusText = 'OK';
        } catch (GaelOException $e) {
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
        };
    }
}
