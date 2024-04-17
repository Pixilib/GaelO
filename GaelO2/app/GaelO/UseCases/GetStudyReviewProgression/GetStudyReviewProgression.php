<?php

namespace App\GaelO\UseCases\GetStudyReviewProgression;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

/**
 * Return array of Reviewer with validated review and Reviewer with no validated review for all Visits of this visitType
 */
class GetStudyReviewProgression
{

    private AuthorizationStudyService $authorizationStudyService;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(
        AuthorizationStudyService $authorizationStudyService,
        VisitRepositoryInterface $visitRepositoryInterface,
        UserRepositoryInterface $userRepositoryInterface,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        StudyRepositoryInterface $studyRepositoryInterface
    ) {
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

    public function execute(GetStudyReviewProgressionRequest $getStudyReviewProgressionRequest, GetStudyReviewProgressionResponse $getStudyReviewProgressionResponse)
    {
        try {
            $studyName = $getStudyReviewProgressionRequest->studyName;

            $this->checkAuthorization($getStudyReviewProgressionRequest->currentUserId, $studyName);

            //Get Reviewers in the asked study
            $reviewers = $this->userRepositoryInterface->getUsersByRolesInStudy($studyName, Constants::ROLE_REVIEWER);

            $reviewersById = [];
            foreach ($reviewers as $reviewer) {
                $reviewersById[$reviewer['id']] = [
                    'lastname' => $reviewer['lastname'],
                    'firstname' => $reviewer['firstname'],
                    'id' => $reviewer['id']
                ];
            }

            $studyEntity = $this->studyRepositoryInterface->find($studyName);
            $originalStudyName = $studyEntity->getOriginalStudyName();

            //Get visits in the asked study (with review status)
            $visits = $this->visitRepositoryInterface->getVisitsInStudy($originalStudyName, true, false, false, $studyName);

            //Get validated review for study
            $validatedReview = $this->reviewRepositoryInterface->getStudyReviewsGroupedByUserIds($studyName);

            $answer = [];

            foreach ($visits as $visit) {

                $visitId = $visit['id'];
                //Listing users having done a review of this visit
                if (key_exists($visitId, $validatedReview)) {
                    $userIdHavingReviewed = array_keys($validatedReview[$visitId]);
                } else {
                    $userIdHavingReviewed = [];
                }

                //Listing users not having done review of this visit
                $userIdNotHavingReviewed = array_diff(array_keys($reviewersById), $userIdHavingReviewed);

                $userDetailsHavingReviewed = [];
                //Get reviewer details having reviewed from db answer (user may have been removed so won't be in the list of current reviewer in the study)
                foreach ($userIdHavingReviewed as $reviewerId) {
                    $userDetailsHavingReviewed[] = $validatedReview[(string) $visitId][(string) $reviewerId][0]['user'];
                }

                $answer[] = [
                    'visitId' => $visitId,
                    'patientId' => $visit['patient_id'],
                    'stateQualityControl' => $visit['state_quality_control'],
                    'reviewStatus' => $visit['review_status']['review_status'],
                    'visitDate' => $visit['visit_date'],
                    'reviewDoneBy' => $userDetailsHavingReviewed,
                    'reviewNotDoneBy' => $this->getUsersDetails($userIdNotHavingReviewed, $reviewersById)
                ];
            }

            $getStudyReviewProgressionResponse->body = $answer;
            $getStudyReviewProgressionResponse->status = 200;
            $getStudyReviewProgressionResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getStudyReviewProgressionResponse->body = $e->getErrorBody();
            $getStudyReviewProgressionResponse->status = $e->statusCode;
            $getStudyReviewProgressionResponse->statusText = $e->statusText;
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

    /**
     * Pickup users details from userId array
     */
    private function getUsersDetails(array $usersId, array $usersDetails): array
    {
        $answer = [];
        foreach ($usersId as $userId) {
            $answer[] = $usersDetails[$userId];
        }

        return $answer;
    }
}
