<?php

namespace App\GaelO\UseCases\GetReviewsFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Entities\ReviewEntity;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetReviewsFromVisitType
{

    private AuthorizationUserService $authorizationUserService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(AuthorizationUserService $authorizationUserService, VisitRepositoryInterface $visitRepositoryInterface, ReviewRepositoryInterface $reviewRepositoryInterface)
    {
        $this->authorizationUserService = $authorizationUserService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

    public function execute(GetReviewsFromVisitTypeRequest $getReviewsFromVisitTypeRequest, GetReviewsFromVisitTypeResponse $getReviewsFromVisitTypeResponse)
    {


        try {

            $studyName = $getReviewsFromVisitTypeRequest->studyName;
            $this->checkAuthorization($getReviewsFromVisitTypeRequest->currentUserId, $studyName);

            //Get Visits in the asked visitTypeId
            $visits = $this->visitRepositoryInterface->getVisitsInVisitType($getReviewsFromVisitTypeRequest->visitTypeId, false, null, false);
            //make visitsId array
            $visitsId = array_map(function($visit){ return $visit['id']; }, $visits);

            //Get Validated review for these visits
            $reviews = $this->reviewRepositoryInterface->getReviewsFromVisitIdArrayStudyName($visitsId, $studyName, false, true);

            $answer = [];

            foreach ($reviews as $review) {
                $reviewEntity = ReviewEntity::fillFromDBReponseArray($review);
                $reviewEntity->setUserDetails($review['user']['lastname'], $review['user']['firstname'], $review['user']['center_code']);
                $answer[] = $reviewEntity;
            }

            $getReviewsFromVisitTypeResponse->body = $answer;
            $getReviewsFromVisitTypeResponse->status = 200;
            $getReviewsFromVisitTypeResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $getReviewsFromVisitTypeResponse->body = $e->getErrorBody();
            $getReviewsFromVisitTypeResponse->status = $e->statusCode;
            $getReviewsFromVisitTypeResponse->statusText = $e->statusText;

        } catch (Exception $e) {
            throw $e;
        }
    }


    private function checkAuthorization(int $userId, string $studyName)
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isRoleAllowed(Constants::ROLE_SUPERVISOR, $studyName)) {
            throw new GaelOForbiddenException();
        };
    }
}
