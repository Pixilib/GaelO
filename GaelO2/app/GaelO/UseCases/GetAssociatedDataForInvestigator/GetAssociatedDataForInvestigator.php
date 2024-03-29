<?php

namespace App\GaelO\UseCases\GetAssociatedDataForInvestigator;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FormService\InvestigatorFormService;
use Exception;

class GetAssociatedDataForInvestigator
{

    private AuthorizationVisitService $authorizationVisitService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private InvestigatorFormService $investigatorFormService;

    public function __construct(AuthorizationVisitService $authorizationVisitService, VisitRepositoryInterface $visitRepositoryInterface, InvestigatorFormService $investigatorFormService)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->investigatorFormService = $investigatorFormService;
    }

    public function execute(GetAssociatedDataForInvestigatorRequest $getAssociatedDataForInvestigatorRequest, GetAssociatedDataForInvestigatorResponse $getAssociatedDataForInvestigatorResponse)
    {
        try {
            $visitId = $getAssociatedDataForInvestigatorRequest->visitId;
            $role = $getAssociatedDataForInvestigatorRequest->role;
            $currentUserId = $getAssociatedDataForInvestigatorRequest->currentUserId;

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId, false);
            $studyName = $visitContext['patient']['study_name'];

            $this->checkAuthorization($currentUserId, $visitId, $studyName, $role, $visitContext);

            $this->investigatorFormService->setCurrentUserId($currentUserId);
            $this->investigatorFormService->setVisitContextAndStudy($visitContext, $studyName);
            $associatedData = $this->investigatorFormService->getVisitDecisionObject()->getAssociatedDataForInvestigatorForm();

            $getAssociatedDataForInvestigatorResponse->body =  $associatedData;
            $getAssociatedDataForInvestigatorResponse->status = 200;
            $getAssociatedDataForInvestigatorResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getAssociatedDataForInvestigatorResponse->body =  $e->getErrorBody();
            $getAssociatedDataForInvestigatorResponse->status = $e->statusCode;
            $getAssociatedDataForInvestigatorResponse->statusText = $e->statusCode;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $visitId, string $studyName, string $role, array $visitContext)
    {
        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setStudyName($studyName);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setVisitContext($visitContext);

        if (!$this->authorizationVisitService->isVisitAllowed($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
