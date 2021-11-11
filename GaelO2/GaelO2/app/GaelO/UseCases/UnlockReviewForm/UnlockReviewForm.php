<?php

namespace App\GaelO\UseCases\UnlockReviewForm;

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

class UnlockReviewForm {

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

    public function execute(UnlockReviewFormRequest $unlockReviewFormRequest, UnlockReviewFormResponse $unlockReviewFormResponse){

        try{

            if(empty($unlockReviewFormRequest->reason)){
                throw new GaelOBadRequestException("Reason must be specified");
            }

            $reviewEntity = $this->reviewRepositoryInterface->find($unlockReviewFormRequest->reviewId);

            $this->checkAuthorization($unlockReviewFormRequest->currentUserId, $unlockReviewFormRequest->reviewId, $reviewEntity['local']);

            $visitContext = $this->visitRepositoryInterface->getVisitContext($reviewEntity['visit_id']);

            $reviewStatus = $this->reviewStatusRepositoryInterface->getReviewStatus($reviewEntity['visit_id'], $reviewEntity['study_name']);

            //Delete review via service review
            $this->reviewFormService->setCurrentUserId($unlockReviewFormRequest->currentUserId);
            $this->reviewFormService->setVisitContextAndStudy($visitContext, $reviewEntity['study_name']);
            $this->reviewFormService->setReviewStatus($reviewStatus);
            $this->reviewFormService->unlockReview($unlockReviewFormRequest->reviewId);

            $actionDetails = [
                'modality' => $visitContext['visit_type']['visit_group']['modality'],
                'visit_type' => $visitContext['visit_type']['name'],
                'patient_id' => $visitContext['patient_id'],
                'id_review' => $unlockReviewFormRequest->reviewId,
                'reason' => $unlockReviewFormRequest->reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $unlockReviewFormRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $reviewEntity['study_name'],
                $reviewEntity['visit_id'],
                Constants::TRACKER_UNLOCK_REVIEW_FORM,
                $actionDetails);

            //send Email notification to review owner
            $this->mailServices->sendUnlockFormMessage(
                $reviewEntity['visit_id'],
                false,
                $unlockReviewFormRequest->currentUserId,
                $reviewEntity['study_name'],
                $visitContext['patient_id'],
                $visitContext['visit_type']['name'] );

            $unlockReviewFormResponse->status = 200;
            $unlockReviewFormResponse->statusText =  'OK';

        } catch (GaelOException $e){

            $unlockReviewFormResponse->body = $e->getErrorBody();
            $unlockReviewFormResponse->status = $e->statusCode;
            $unlockReviewFormResponse->statusText =  $e->statusText;

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
