<?php

namespace App\GaelO\UseCases\GetReviewProgression;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

/**
 * Return array of Reviewer with validated review and Reviewer with no validated review for all Visits of this visitType
 */
class GetReviewProgression {

    private AuthorizationService $authorizationService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(AuthorizationService $authorizationService,
                                VisitRepositoryInterface $visitRepositoryInterface,
                                UserRepositoryInterface $userRepositoryInterface,
                                ReviewRepositoryInterface $reviewRepositoryInterface){

        $this->authorizationService = $authorizationService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;

    }

    public function execute(GetReviewProgressionRequest $getReviewProgressionRequest, GetReviewProgressionResponse $getReviewProgressionResponse){
        try{

            $this->checkAuthorization($getReviewProgressionRequest->currentUserId, $getReviewProgressionRequest->studyName);

            //Get Reviewers in the asked study
            $reviewers = $this->userRepositoryInterface->getUsersByRolesInStudy($getReviewProgressionRequest->studyName, Constants::ROLE_REVIEWER);

            $reviewersById = [];
            foreach ( $reviewers as $reviewer ) {
                $reviewersById[$reviewer['id']] = [
                    'username' => $reviewer['username'],
                    'lastname' => $reviewer['lastname'],
                    'firstname' => $reviewer['firstname']
                ];
            }

            //Get Visits in the asked visitTypeId and Study (with review status)
            $visits = $this->visitRepositoryInterface->getVisitsInVisitType($getReviewProgressionRequest->visitTypeId, true, $getReviewProgressionRequest->studyName);

            //Get Validated review for VisitType and Study
            $validatedReview = $this->reviewRepositoryInterface->getUsersHavingReviewedForStudyVisitType($getReviewProgressionRequest->studyName, $getReviewProgressionRequest->visitTypeId);

            $answer = [];

            foreach ($visits as $visit){

                //Listing users having done a review of this visit
                if(key_exists($visit['id'], $validatedReview)) $userIdHavingReviewed = array_keys($validatedReview[$visit['id']]);
                else $userIdHavingReviewed = [];

                //Listing users not having done review of this visit
                $userIdNotHavingReviewed = array_diff(array_keys($reviewersById), $userIdHavingReviewed);


                $answer[] = [
                    'id' => $visit['id'],
                    'patientCode' => $visit['patient_code'],
                    'reviewStatus' => $visit['review_status']['review_status'],
                    'visitDate' =>$visit['visit_date'],
                    'reviewDoneBy'=> $this->getUsersDetails($userIdHavingReviewed, $reviewersById),
                    'reviewNotDoneBy'=>$this->getUsersDetails($userIdNotHavingReviewed, $reviewersById)
                ];
            }

            $getReviewProgressionResponse->body = $answer;
            $getReviewProgressionResponse->status = 200;
            $getReviewProgressionResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $getReviewProgressionResponse->body = $e->getErrorBody();
            $getReviewProgressionResponse->status = $e->statusCode;
            $getReviewProgressionResponse->statusText = $e->statusText;

        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $studyName){
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        if(! $this->authorizationService->isRoleAllowed($studyName)){
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
