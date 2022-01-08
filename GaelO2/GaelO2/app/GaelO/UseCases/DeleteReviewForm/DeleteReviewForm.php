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
use App\GaelO\Services\AuthorizationService\AuthorizationReviewService;
use App\GaelO\Services\FormService\ReviewFormService;
use App\GaelO\Services\MailServices;
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

            $studyName = $reviewEntity['study_name'];
            $local = $reviewEntity['local'];
            $visitId = $reviewEntity['visit_id'];

            $this->checkAuthorization($deleteReviewFormRequest->currentUserId, $deleteReviewFormRequest->reviewId, $local);

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);

            $reviewStatus = $this->reviewStatusRepositoryInterface->getReviewStatus($visitId, $studyName);

            //Delete review via service review
            $this->reviewFormService->setCurrentUserId($deleteReviewFormRequest->currentUserId);
            $this->reviewFormService->setVisitContextAndStudy($visitContext, $studyName);
            $this->reviewFormService->setReviewStatus($reviewStatus);
            $this->reviewFormService->deleteReview($deleteReviewFormRequest->reviewId);

            $actionDetails = [
                'modality' => $visitContext['visit_type']['visit_group']['modality'],
                'visit_type' => $visitContext['visit_type']['name'],
                'patient_id' => $visitContext['patient_id'],
                'id_review' => $deleteReviewFormRequest->reviewId,
                'reason' => $deleteReviewFormRequest->reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $deleteReviewFormRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $visitId,
                Constants::TRACKER_DELETE_REVIEW_FORM,
                $actionDetails);

            //send Email notification to review owner
            $this->mailServices->sendDeleteFormMessage(
                $visitId,
                false,
                $reviewEntity['user_id'],
                $studyName,
                $visitContext['patient_id'],
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
        $this->authorizationReviewService->setUserId($currentUserId);
        $this->authorizationReviewService->setReviewId($reviewId);
        if( !$this->authorizationReviewService->isReviewAllowed(Constants::ROLE_SUPERVISOR) ) {
            throw new GaelOForbiddenException();
        }
    }
}
