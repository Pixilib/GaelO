<?php

namespace App\GaelO\UseCases\GetReviewsFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetReviewForm\ReviewFormEntity;
use Exception;

class GetReviewsFromVisitType
{

    private AuthorizationService $authorizationService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(AuthorizationService $authorizationService, VisitRepositoryInterface $visitRepositoryInterface, ReviewRepositoryInterface $reviewRepositoryInterface)
    {
        $this->authorizationService = $authorizationService;
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
            $reviews = $this->reviewRepositoryInterface->getReviewFromVisitIdArrayStudyName($visitsId, $studyName, false);

            $answer = [];

            foreach ($reviews as $review) {

                $reviewEntity = ReviewFormEntity::fillFromDBReponseArray($review);
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
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        if (!$this->authorizationService->isRoleAllowed($studyName)) {
            throw new GaelOForbiddenException();
        };
    }
}
