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
use App\GaelO\Services\AuthorizationService\AuthorizationReviewService;
use App\GaelO\Services\FormService\ReviewFormService;
use App\GaelO\Services\MailServices;
use Exception;

class UnlockReviewForm
{

    private AuthorizationReviewService $authorizationReviewService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private ReviewFormService $reviewFormService;
    private ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    private MailServices $mailServices;

    public function __construct(
        AuthorizationReviewService $authorizationReviewService,
        ReviewFormService $reviewFormService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        MailServices $mailServices
    ) {
        $this->authorizationReviewService = $authorizationReviewService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
        $this->reviewFormService = $reviewFormService;
        $this->mailServices = $mailServices;
    }

    public function execute(UnlockReviewFormRequest $unlockReviewFormRequest, UnlockReviewFormResponse $unlockReviewFormResponse)
    {

        try {

            if (empty($unlockReviewFormRequest->reason)) {
                throw new GaelOBadRequestException("Reason must be specified");
            }

            $reviewEntity = $this->reviewRepositoryInterface->find($unlockReviewFormRequest->reviewId);

            $currentUserId = $unlockReviewFormRequest->currentUserId;
            $reviewId = $reviewEntity['id'];
            $reason = $unlockReviewFormRequest->reason;

            /* Search for validated adjudication review */
            $studyVisitReviews = $this->reviewRepositoryInterface->getReviewsForStudyVisit($reviewEntity['study_name'], $reviewEntity['visit_id'], true);
            $existingAdjudicationForm = array_search(true, array_map(function ($review) {
                return $review['adjudication'];
            }, $studyVisitReviews));
            /* If validated adjudication review exist, this review can't be unlocked */
            if ($existingAdjudicationForm) throw new GaelOBadRequestException('Please delete adjudication form before unlocking this review');

            $this->checkAuthorization($currentUserId, $reviewId, $reviewEntity['local']);

            $visitContext = $this->visitRepositoryInterface->getVisitContext($reviewEntity['visit_id']);

            $reviewStatus = $this->reviewStatusRepositoryInterface->getReviewStatus($reviewEntity['visit_id'], $reviewEntity['study_name']);

            //Delete review via service review
            $this->reviewFormService->setCurrentUserId($currentUserId);
            $this->reviewFormService->setVisitContextAndStudy($visitContext, $reviewEntity['study_name']);
            $this->reviewFormService->setReviewStatus($reviewStatus);
            $this->reviewFormService->unlockReview($reviewId);

            $actionDetails = [
                'visit_group_name' =>$visitContext['visit_type']['visit_group']['name'],
                'visit_group_modality' => $visitContext['visit_type']['visit_group']['modality'],
                'visit_type_name' => $visitContext['visit_type']['name'],
                'patient_id' => $visitContext['patient_id'],
                'review_id' => $reviewId,
                'reason' => $reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_SUPERVISOR,
                $reviewEntity['study_name'],
                $reviewEntity['visit_id'],
                Constants::TRACKER_UNLOCK_REVIEW_FORM,
                $actionDetails
            );

            //send Email notification to review owner
            $this->mailServices->sendUnlockedFormMessage(
                $reviewEntity['visit_id'],
                false,
                $currentUserId,
                $reviewEntity['study_name'],
                $visitContext['patient_id'],
                $visitContext['patient']['code'],
                $visitContext['visit_type']['name']
            );

            $unlockReviewFormResponse->status = 200;
            $unlockReviewFormResponse->statusText =  'OK';
        } catch (GaelOException $e) {
            $unlockReviewFormResponse->body = $e->getErrorBody();
            $unlockReviewFormResponse->status = $e->statusCode;
            $unlockReviewFormResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $reviewId, bool $local)
    {
        if ($local) {
            throw new GaelOForbiddenException();
        }
        $this->authorizationReviewService->setUserId($currentUserId);
        $this->authorizationReviewService->setReviewId($reviewId);
        if (!$this->authorizationReviewService->isReviewAllowed(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
