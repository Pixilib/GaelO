<?php

namespace App\GaelO\UseCases\GetAssociatedDataForReview;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
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

    public function __construct(AuthorizationVisitService $authorizationVisitService, VisitRepositoryInterface $visitRepositoryInterface, ReviewFormService $reviewFormService)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->reviewFormService = $reviewFormService;
    }

    public function execute(GetAssociatedDataForReviewRequest $getAssociatedDataForReviewRequest, GetAssociatedDataForReviewResponse $getAssociatedDataForReviewResponse)
    {
        try {

            $this->checkAuthorization($getAssociatedDataForReviewRequest->currentUserId, $getAssociatedDataForReviewRequest->visitId, $getAssociatedDataForReviewRequest->studyName);

            $visitContext = $this->visitRepositoryInterface->getVisitContext($getAssociatedDataForReviewRequest->visitId);

            $this->reviewFormService->setCurrentUserId($getAssociatedDataForReviewRequest->currentUserId);
            $this->reviewFormService->setVisitContextAndStudy($visitContext, $getAssociatedDataForReviewRequest->studyName);
            //SK TODO A gerer ici le statut de investigator ou reviewer dans le form service
            // SK TODO EXPOSER LA ROUTE ET FAIRE LE TEST
            $associatedData = $this->reviewFormService->getAssociatedDataForForm();

            $getAssociatedDataForReviewResponse->body =  $associatedData;
            $getAssociatedDataForReviewResponse->status = 200;
            $getAssociatedDataForReviewResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $getAssociatedDataForReviewResponse->body =  $e->getErrorBody();
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
