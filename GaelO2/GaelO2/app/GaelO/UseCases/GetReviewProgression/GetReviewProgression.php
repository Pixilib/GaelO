<?php

namespace App\GaelO\UseCases\GetReviewProgression;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Interfaces\UserRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use Exception;

/**
 * Return array of Reviewer with validated review and Reviewer with no validated review for all Visits of this visitType
 */
class GetReviewProgression {

    private VisitRepositoryInterface $visitRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface,
                                UserRepositoryInterface $userRepositoryInterface,
                                ReviewRepositoryInterface $reviewRepositoryInterface){

        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;

    }

    public function execute(GetReviewProgressionRequest $getReviewProgressionRequest, GetReviewProgressionResponse $getReviewProgressionResponse){
        try{

            //SK CHECK AUORIZATION A FAIRE

            //Get Reviewers in the asked study
            $reviewers = $this->userRepositoryInterface->getUsersByRolesInStudy($getReviewProgressionRequest->studyName, Constants::ROLE_REVIEWER);
            $reviewersId = array_map(function ($reviewer) {
                return $reviewer['id'];
            }, $reviewers);

            //Get Visits in the asked visitTypeId and Study (with review status)
            $visits = $this->visitRepositoryInterface->getVisitsInVisitType($getReviewProgressionRequest->visitTypeId, true, $getReviewProgressionRequest->studyName);

            //Get Validated review for VisitType and Study
            $validatedReview = $this->reviewRepositoryInterface->getUsersHavingReviewedForStudyVisitType($getReviewProgressionRequest->studyName, $getReviewProgressionRequest->visitTypeId);

            $answer = [];

            foreach ($visits as $visit){

                $userIdHavingReviewed = array_keys($validatedReview[$visit['id']]);
                $userIdNotHavingReviewed = array_diff($reviewersId, $userIdHavingReviewed);
                $answer[] = [
                    'id' => $visit['id'],
                    'patientCode' => $visit['patient_code'],
                    'reviewStatus' => $visit['review_status'],
                    'visitDate' =>$visit['visit_date'],
                    'reviewDoneBy'=>$userIdHavingReviewed,
                    'reviewNotDoneBy'=>$userIdNotHavingReviewed
                ];
            }
            dd($reviewers, $visits, $validatedReview, $answer);

            //Compute missing reviewer and outputresults
            //SK A FAIRE

            $getReviewProgressionResponse->body = 'SA';
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

    private function checkAuthorization() {
        //Check user is supervisor in the called Study

    }

}
