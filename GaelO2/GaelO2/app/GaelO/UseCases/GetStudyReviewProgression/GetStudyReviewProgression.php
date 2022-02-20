<?php

namespace App\GaelO\UseCases\GetStudyReviewProgression;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\StudyService;
use Exception;

/**
 * Return array of Reviewer with validated review and Reviewer with no validated review for all Visits of this visitType
 */
class GetStudyReviewProgression {

    private AuthorizationStudyService $authorizationStudyService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private StudyService $studyService;

    public function __construct(AuthorizationStudyService $authorizationStudyService,
                                VisitRepositoryInterface $visitRepositoryInterface,
                                UserRepositoryInterface $userRepositoryInterface,
                                ReviewRepositoryInterface $reviewRepositoryInterface,
                                StudyRepositoryInterface $studyRepositoryInterface,
                                StudyService $studyService){
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->studyService = $studyService;

    }

    public function execute(GetStudyReviewProgressionRequest $getStudyReviewProgressionRequest, GetStudyReviewProgressionResponse $getStudyReviewProgressionResponse){
        try{

            $this->checkAuthorization($getStudyReviewProgressionRequest->currentUserId, $getStudyReviewProgressionRequest->studyName);
            //Get Reviewers in the asked study
            $reviewers = $this->userRepositoryInterface->getUsersByRolesInStudy($getStudyReviewProgressionRequest->studyName, Constants::ROLE_REVIEWER);

            $reviewersById = [];
            foreach ( $reviewers as $reviewer ) {
                $reviewersById[$reviewer['id']] = [
                    'lastname' => $reviewer['lastname'],
                    'firstname' => $reviewer['firstname']
                ];
            }

            $studyEntity = $this->studyRepositoryInterface->find($getStudyReviewProgressionRequest->studyName);

            $this->studyService->setStudyEntity($studyEntity);
            $studyName = $this->studyService->getOriginalStudyName();

            //Get visits in the asked study (with review status)
            $visits = $this->visitRepositoryInterface->getVisitsInStudy($studyName, true, false);

            //Get validated review for study
            $validatedReview = $this->reviewRepositoryInterface->getStudyReviewsGroupedByUserIds($getStudyReviewProgressionRequest->studyName);

            $answer = [];

            foreach ($visits as $visit){

                //Listing users having done a review of this visit
                if(key_exists($visit['id'], $validatedReview)) $userIdHavingReviewed = array_keys($validatedReview[$visit['id']]);
                else $userIdHavingReviewed = [];

                //Listing users not having done review of this visit
                $userIdNotHavingReviewed = array_diff(array_keys($reviewersById), $userIdHavingReviewed);


                $answer[] = [
                    'visitId' => $visit['id'],
                    'patientId' => $visit['patient_id'],
                    'reviewStatus' => $visit['review_status']['review_status'],
                    'visitDate' =>$visit['visit_date'],
                    'reviewDoneBy'=> $this->getUsersDetails($userIdHavingReviewed, $reviewersById),
                    'reviewNotDoneBy'=>$this->getUsersDetails($userIdNotHavingReviewed, $reviewersById)
                ];
            }

            $getStudyReviewProgressionResponse->body = $answer;
            $getStudyReviewProgressionResponse->status = 200;
            $getStudyReviewProgressionResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $getStudyReviewProgressionResponse->body = $e->getErrorBody();
            $getStudyReviewProgressionResponse->status = $e->statusCode;
            $getStudyReviewProgressionResponse->statusText = $e->statusText;

        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $studyName){
        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($studyName);
        if(! $this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)){
            throw new GaelOForbiddenException();
        };
    }

    /**
     * Pickup users details from userId array
     */
    private function getUsersDetails(array $usersId, array $usersDetails) : array {
        $answer=[];
        foreach($usersId as $userId){
            $answer[]=$usersDetails[$userId];
        }

        return $answer;
    }

}
