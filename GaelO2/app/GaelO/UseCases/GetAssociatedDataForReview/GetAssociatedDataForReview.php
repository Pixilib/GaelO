<?php

namespace App\GaelO\UseCases\GetAssociatedDataForReview;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FormService\ReviewFormService;
use Exception;

class GetAssociatedDataForReview
{

    private AuthorizationVisitService $authorizationVisitService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private ReviewFormService $reviewFormService;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService, 
        VisitRepositoryInterface $visitRepositoryInterface,
        ReviewFormService $reviewFormService)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->reviewFormService = $reviewFormService;
    }

    public function execute(GetAssociatedDataForReviewRequest $getAssociatedDataForReviewRequest, GetAssociatedDataForReviewResponse $getAssociatedDataForReviewResponse)
    {
        try {
            $currentUserId = $getAssociatedDataForReviewRequest->currentUserId;
            $visitId = $getAssociatedDataForReviewRequest->visitId;
            $studyName = $getAssociatedDataForReviewRequest->studyName;

            $visitContext = $this->visitRepositoryInterface->getVisitWithContextAndReviewStatus($visitId, $studyName);
            $this->checkAuthorization($currentUserId, $visitId, $studyName, $visitContext);

            $this->reviewFormService->setCurrentUserId($currentUserId);
            $this->reviewFormService->setVisitContextAndStudy($visitContext, $studyName);
            //NB switch depending on adjudication should be decided in the function in visit rule (visit context is passed)
            $associatedData = $this->reviewFormService->getVisitDecisionObject()->getAssociatedDataForReviewForm();

            $getAssociatedDataForReviewResponse->body = $associatedData;
            $getAssociatedDataForReviewResponse->status = 200;
            $getAssociatedDataForReviewResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getAssociatedDataForReviewResponse->body = $e->getErrorBody();
            $getAssociatedDataForReviewResponse->status = $e->statusCode;
            $getAssociatedDataForReviewResponse->statusText = $e->statusCode;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $visitId, string $studyName, array $visitContext)
    {
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setStudyName($studyName);
        $this->authorizationVisitService->setVisitContext($visitContext);

        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_REVIEWER)) {
            throw new GaelOForbiddenException();
        }
    }
}
