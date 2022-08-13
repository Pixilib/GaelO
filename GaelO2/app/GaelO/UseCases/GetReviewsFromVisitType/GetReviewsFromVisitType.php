<?php

namespace App\GaelO\UseCases\GetReviewsFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Entities\ReviewEntity;
use App\GaelO\Entities\UserEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetReviewsFromVisitType
{

    private AuthorizationStudyService $authorizationStudyService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(AuthorizationStudyService $authorizationStudyService, VisitRepositoryInterface $visitRepositoryInterface, ReviewRepositoryInterface $reviewRepositoryInterface)
    {
        $this->authorizationStudyService = $authorizationStudyService;
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
            $visitsId = array_map(function ($visit) {
                return $visit['id'];
            }, $visits);

            //Get Validated review for these visits
            $reviews = $this->reviewRepositoryInterface->getReviewsFromVisitIdArrayStudyName($visitsId, $studyName, false, true);

            $answer = [];

            foreach ($reviews as $review) {
                $reviewEntity = ReviewEntity::fillFromDBReponseArray($review);
                $reviewEntity->setUserDetails(UserEntity::fillMinimalFromDBReponseArray($review['user']));
                $answer[] = $reviewEntity;
            }

            $getReviewsFromVisitTypeResponse->body = $answer;
            $getReviewsFromVisitTypeResponse->status = 200;
            $getReviewsFromVisitTypeResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getReviewsFromVisitTypeResponse->body = $e->getErrorBody();
            $getReviewsFromVisitTypeResponse->status = $e->statusCode;
            $getReviewsFromVisitTypeResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }


    private function checkAuthorization(int $userId, string $studyName)
    {
        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
