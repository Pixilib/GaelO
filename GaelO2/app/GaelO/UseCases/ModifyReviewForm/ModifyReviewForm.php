<?php

namespace App\GaelO\UseCases\ModifyReviewForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FormService\ReviewFormService;
use Exception;

class ModifyReviewForm
{

    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private ReviewFormService $reviewFormService;
    private AuthorizationVisitService $authorizationVisitService;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface,
        ReviewFormService $reviewFormService,
        TrackerRepositoryInterface $trackerRepositoryInterface
    ) {
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
        $this->reviewFormService = $reviewFormService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
    }

    public function execute(ModifyReviewFormRequest $modifyReviewFormRequest, ModifyReviewFormResponse $modifyReviewFormResponse)
    {

        try {

            $currentUserId = $modifyReviewFormRequest->currentUserId;

            if (!isset($modifyReviewFormRequest->validated)) {
                throw new GaelOBadRequestException('Validated Status is mandatory');
            }

            $reviewEntity = $this->reviewRepositoryInterface->find($modifyReviewFormRequest->reviewId);
            $visitContext = $this->visitRepositoryInterface->getVisitContext($reviewEntity['visit_id']);
            $reviewStatus = $this->reviewStatusRepositoryInterface->getReviewStatus($reviewEntity['visit_id'], $reviewEntity['study_name']);

            $this->checkAuthorization($currentUserId, $reviewEntity['user_id'], $reviewEntity['validated'], $reviewStatus['review_available'], $reviewEntity['visit_id'],  $reviewEntity['study_name']);

            //Call service to update form
            $this->reviewFormService->setCurrentUserId($currentUserId);
            $this->reviewFormService->setReviewStatus($reviewStatus);
            $this->reviewFormService->setVisitContextAndStudy($visitContext, $reviewEntity['study_name']);
            $this->reviewFormService->updateReview($reviewEntity['id'], $modifyReviewFormRequest->data, $modifyReviewFormRequest->validated);

            //Write in Tracker
            $actionDetails = [
                'idReview' => $reviewEntity['id'],
                'adjudication' => $reviewStatus['review_status'] === Constants::REVIEW_STATUS_WAIT_ADJUDICATION,
                'raw_data' => $modifyReviewFormRequest->data,
                'validated' => $modifyReviewFormRequest->validated
            ];

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::ROLE_REVIEWER, $reviewEntity['study_name'], $reviewEntity['visit_id'], Constants::TRACKER_MODIFY_REVIEWER_FORM, $actionDetails);

            $modifyReviewFormResponse->body = ['id' => $reviewEntity['id']];
            $modifyReviewFormResponse->status = 200;
            $modifyReviewFormResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $modifyReviewFormResponse->body = $e->getErrorBody();
            $modifyReviewFormResponse->status = $e->statusCode;
            $modifyReviewFormResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $formOwner, bool $formValidated, bool $reviewAvailability, int $visitId, string $studyName)
    {
        //Asked edition review should be owned by current user, not yet validated and in a visit still allowing review
        if ($currentUserId !== $formOwner || $formValidated || !$reviewAvailability) {
            throw new GaelOForbiddenException();
        }
        //Check role reviewer is still available for this user (even if it own the form, his role could have been removed)
        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);
        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_REVIEWER)) {
            throw new GaelOForbiddenException();
        };
    }
}
