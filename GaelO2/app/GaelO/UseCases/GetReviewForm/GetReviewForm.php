<?php

namespace App\GaelO\UseCases\GetReviewForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\ReviewEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationReviewService;
use Exception;

class GetReviewForm
{

    private AuthorizationReviewService $authorizationReviewService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(
        AuthorizationReviewService $authorizationReviewService,
        ReviewRepositoryInterface $reviewRepositoryInterface
    ) {
        $this->authorizationReviewService = $authorizationReviewService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

    public function execute(GetReviewFormRequest $getReviewFormRequest, GetReviewFormResponse $getReviewFormResponse)
    {

        try {

            $this->checkAuthorization($getReviewFormRequest->currentUserId, $getReviewFormRequest->reviewId);

            $reviewEntity = $this->reviewRepositoryInterface->find($getReviewFormRequest->reviewId);
            $review = ReviewEntity::fillFromDBReponseArray($reviewEntity);

            $getReviewFormResponse->body = $review;
            $getReviewFormResponse->status = 200;
            $getReviewFormResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getReviewFormResponse->body = $e->getErrorBody();
            $getReviewFormResponse->status = $e->statusCode;
            $getReviewFormResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $reviewId)
    {
        $this->authorizationReviewService->setUserId($currentUserId);
        $this->authorizationReviewService->setReviewId($reviewId);
        if (!$this->authorizationReviewService->isReviewAllowed(Constants::ROLE_REVIEWER)) {
            throw new GaelOForbiddenException();
        }
    }
}
