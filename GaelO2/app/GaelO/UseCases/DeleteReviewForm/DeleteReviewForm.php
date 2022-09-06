<?php

namespace App\GaelO\UseCases\DeleteReviewForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationReviewService;
use App\GaelO\Services\FormService\ReviewFormService;
use App\GaelO\Services\MailServices;
use Exception;

class DeleteReviewForm
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

    public function execute(DeleteReviewFormRequest $deleteReviewFormRequest, DeleteReviewFormResponse $deleteReviewFormResponse)
    {

        try {

            if (empty($deleteReviewFormRequest->reason)) throw new GaelOBadRequestException("Reason must be specified");

            $reviewEntity = $this->reviewRepositoryInterface->find($deleteReviewFormRequest->reviewId);

            $studyName = $reviewEntity['study_name'];
            $local = $reviewEntity['local'];
            $visitId = $reviewEntity['visit_id'];
            $reviewId = $reviewEntity['id'];
            $adjudication = $reviewEntity['adjudication'];
            $currentUserId = $deleteReviewFormRequest->currentUserId;
            $reason = $deleteReviewFormRequest->reason;


            /* if try to delete a non adjudication form, verify that no validated adjudication review exists that would need to be unlocked/deleted first */
            if (!$adjudication) {

                $studyVisitReviews = $this->reviewRepositoryInterface->getReviewsForStudyVisit($studyName, $visitId, true);

                $existingAdjudicationForm = array_search(true, array_map(function ($review) {
                    return $review['adjudication'];
                }, $studyVisitReviews));

                /* If validated review exist (different from false strictly typed as if adjudciation is position 0 will be falsy), this review can't be deleted */
                if ($existingAdjudicationForm !== false) throw new GaelOForbiddenException('Please delete adjudication form before deleting this review');
            }

            $this->checkAuthorization($currentUserId, $reviewId, $local);

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
            $reviewStatus = $this->reviewStatusRepositoryInterface->getReviewStatus($visitId, $studyName);

            //Delete review via service review
            $this->reviewFormService->setCurrentUserId($currentUserId);
            $this->reviewFormService->setVisitContextAndStudy($visitContext, $studyName);
            $this->reviewFormService->setReviewStatus($reviewStatus);
            $this->reviewFormService->deleteReview($reviewId);

            $actionDetails = [
                'visit_group_name' => $visitContext['visit_type']['visit_group']['name'],
                'modality' => $visitContext['visit_type']['visit_group']['modality'],
                'visit_type' => $visitContext['visit_type']['name'],
                'patient_id' => $visitContext['patient_id'],
                'id_review' => $reviewId,
                'reason' => $reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $visitId,
                Constants::TRACKER_DELETE_REVIEWER_FORM,
                $actionDetails
            );

            //send Email notification to review owner
            $this->mailServices->sendDeleteFormMessage(
                $visitId,
                false,
                $reviewEntity['user_id'],
                $studyName,
                $visitContext['patient_id'],
                $visitContext['patient']['code'],
                $visitContext['visit_type']['name']
            );

            $deleteReviewFormResponse->status = 200;
            $deleteReviewFormResponse->statusText =  'OK';
        } catch (AbstractGaelOException $e) {
            $deleteReviewFormResponse->body = $e->getErrorBody();
            $deleteReviewFormResponse->status = $e->statusCode;
            $deleteReviewFormResponse->statusText =  $e->statusText;
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
