<?php

namespace App\GaelO\UseCases\DeleteReviewForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationReviewService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\ReviewFormService;
use Exception;

class DeleteReviewForm {

    private AuthorizationReviewService $authorizationReviewService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private ReviewFormService $reviewFormService;
    private ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    private MailServices $mailServices;

    public function __construct(AuthorizationReviewService $authorizationReviewService,
        ReviewFormService $reviewFormService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        MailServices $mailServices
        ){
        $this->authorizationReviewService = $authorizationReviewService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
        $this->reviewFormService = $reviewFormService;
        $this->mailServices = $mailServices;
    }

    public function execute(DeleteReviewFormRequest $deleteReviewFormRequest, DeleteReviewFormResponse $deleteReviewFormResponse){

        try{

            if(empty($deleteReviewFormRequest->reason)){
                throw new GaelOBadRequestException("Reason must be specified");
            }

            $reviewEntity = $this->reviewRepositoryInterface->find($deleteReviewFormRequest->reviewId);

            $this->checkAuthorization($deleteReviewFormRequest->currentUserId, $deleteReviewFormRequest->reviewId, $reviewEntity['local']);

            $visitContext = $this->visitRepositoryInterface->getVisitContext($reviewEntity['visit_id']);

            $reviewStatus = $this->reviewStatusRepositoryInterface->getReviewStatus($reviewEntity['visit_id'], $reviewEntity['study_name']);

            //Delete review via service review
            $this->reviewFormService->setCurrentUserId($deleteReviewFormRequest->currentUserId);
            $this->reviewFormService->setVisitContextAndStudy($visitContext, $reviewEntity['study_name']);
            $this->reviewFormService->setReviewStatus($reviewStatus);
            $this->reviewFormService->deleteReview($deleteReviewFormRequest->reviewId);

            $actionDetails = [
                'modality' => $visitContext['visit_type']['visit_group']['modality'],
                'visit_type' => $visitContext['visit_type']['name'],
                'patient_code' => $visitContext['patient_code'],
                'id_review' => $deleteReviewFormRequest->reviewId,
                'reason' => $deleteReviewFormRequest->reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $deleteReviewFormRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $reviewEntity['study_name'],
                $reviewEntity['visit_id'],
                Constants::TRACKER_DELETE_REVIEW_FORM,
                $actionDetails);

            //send Email notification to review owner
            $this->mailServices->sendDeleteFormMessage(false,
                $reviewEntity['user_id'],
                $reviewEntity['study_name'],
                $visitContext['patient_code'],
                $visitContext['visit_type']['name'] );

            $deleteReviewFormResponse->status = 200;
            $deleteReviewFormResponse->statusText =  'OK';

        } catch (GaelOException $e){

            $deleteReviewFormResponse->body = $e->getErrorBody();
            $deleteReviewFormResponse->status = $e->statusCode;
            $deleteReviewFormResponse->statusText =  $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, int $reviewId, bool $local){
        if($local){
            throw new GaelOForbiddenException();
        }
        $this->authorizationReviewService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        $this->authorizationReviewService->setReviewId($reviewId);
        if( !$this->authorizationReviewService->isReviewAllowed() ) {
            throw new GaelOForbiddenException();
        }
    }
}
