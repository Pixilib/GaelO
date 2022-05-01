<?php

namespace App\GaelO\UseCases\GetReviewFormFromVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Entities\ReviewEntity;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use Exception;

class GetReviewFormFromVisit
{

    private AuthorizationVisitService $authorizationVisitService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
    ) {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

    public function execute(GetReviewFormFromVisitRequest $getReviewFormFromVisitRequest, GetReviewFormFromVisitResponse $getReviewFormFromVisitResponse)
    {

        try {

            $this->checkAuthorization($getReviewFormFromVisitRequest->currentUserId, $getReviewFormFromVisitRequest->visitId, $getReviewFormFromVisitRequest->userId, $getReviewFormFromVisitRequest->studyName);

            $reviews = [];

            if( !$getReviewFormFromVisitRequest->userId){
                $reviewEntities = $this->reviewRepositoryInterface->getReviewsForStudyVisit($getReviewFormFromVisitRequest->studyName, $getReviewFormFromVisitRequest->visitId, false);
                foreach ($reviewEntities as $review) {
                    $detailedReview = ReviewEntity::fillFromDBReponseArray($review);
                    $detailedReview->setUserDetails($review['user']['lastname'], $review['user']['firstname'], $review['user']['center_code']);
                    $reviews[] = $detailedReview;
                }
            }else{
                $reviewEntity = $this->reviewRepositoryInterface->getReviewFormForStudyVisitUser($getReviewFormFromVisitRequest->studyName, $getReviewFormFromVisitRequest->visitId, $getReviewFormFromVisitRequest->userId);
                $detailedReview = ReviewEntity::fillFromDBReponseArray($reviewEntity);
                $reviews = $detailedReview;
            }

            $getReviewFormFromVisitResponse->body = $reviews;
            $getReviewFormFromVisitResponse->status = 200;
            $getReviewFormFromVisitResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $getReviewFormFromVisitResponse->body = $e->getErrorBody();
            $getReviewFormFromVisitResponse->status = $e->statusCode;
            $getReviewFormFromVisitResponse->statusText = $e->statusText;

        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $visitId, ?int $reviewerId, string $studyName)
    {
        if ($currentUserId === $reviewerId) {
            return true;
        }

        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setStudyName($studyName);
        $this->authorizationVisitService->setVisitId($visitId);
        if ( !$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_SUPERVISOR) ) {
            throw new GaelOForbiddenException();
        }

    }
}
