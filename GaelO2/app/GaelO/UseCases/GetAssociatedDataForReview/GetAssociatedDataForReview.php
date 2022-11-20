<?php

namespace App\GaelO\UseCases\GetAssociatedDataForReview;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\GaelOStudiesService\AbstractGaelOStudy;
use Exception;

class GetAssociatedDataForReview
{

    private AuthorizationVisitService $authorizationVisitService;
    private VisitRepositoryInterface $visitRepositoryInterface;

    public function __construct(AuthorizationVisitService $authorizationVisitService, VisitRepositoryInterface $visitRepositoryInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function execute(GetAssociatedDataForReviewRequest $getAssociatedDataForReviewRequest, GetAssociatedDataForReviewResponse $getAssociatedDataForReviewResponse)
    {
        try {
            $currentUserId = $getAssociatedDataForReviewRequest->currentUserId;
            $visitId = $getAssociatedDataForReviewRequest->visitId;
            $studyName = $getAssociatedDataForReviewRequest->studyName;

            $this->checkAuthorization($currentUserId, $visitId, $studyName);

            $visitContext = $this->visitRepositoryInterface->getVisitWithContextAndReviewStatus($visitId, $studyName);

            $visitGroup = $visitContext['visit_type']['visit_group']['name'];
            $visitType = $visitContext['visit_type']['name'];
            $associatedData = AbstractGaelOStudy::getSpecificStudiesRules($studyName, $visitGroup, $visitType)->getAssociatedDataForReviewForm();

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

    private function checkAuthorization(int $currentUserId, int $visitId, string $studyName)
    {
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setStudyName($studyName);
        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_REVIEWER)) {
            throw new GaelOForbiddenException();
        }
    }
}
