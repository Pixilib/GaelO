<?php

namespace App\GaelO\UseCases\ModifyReviewForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FormService\ReviewFormService;
use Exception;

class ModifyReviewForm
{

    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private ReviewFormService $reviewFormService;
    private AuthorizationVisitService $authorizationVisitService;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        ReviewFormService $reviewFormService,
        TrackerRepositoryInterface $trackerRepositoryInterface
    ) {
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
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
            $studyName = $reviewEntity['study_name'];
            $visitId = $reviewEntity['visit_id'];
            $reviewId = $reviewEntity['id'];
            $uploadedFileKeys = array_keys($reviewEntity['sent_files']);

            $visitContext = $this->visitRepositoryInterface->getVisitWithContextAndReviewStatus($visitId, $studyName);
            $reviewStatus = $visitContext['review_status']['review_status'];
            $this->checkAuthorization($currentUserId, $reviewEntity, $visitId,  $studyName, $visitContext);
            

            //Call service to update form
            $this->reviewFormService->setCurrentUserId($currentUserId);
            $this->reviewFormService->setVisitContextAndStudy($visitContext, $studyName);
            $this->reviewFormService->updateForm($reviewId, $uploadedFileKeys, $modifyReviewFormRequest->data, $modifyReviewFormRequest->validated);

            //Write in Tracker
            $actionDetails = [
                'review_id' => $reviewId,
                'adjudication' => $reviewStatus === Constants::REVIEW_STATUS_WAIT_ADJUDICATION,
                'raw_data' => $modifyReviewFormRequest->data,
                'validated' => $modifyReviewFormRequest->validated
            ];

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::ROLE_REVIEWER, $studyName, $visitId, Constants::TRACKER_MODIFY_REVIEWER_FORM, $actionDetails);

            $modifyReviewFormResponse->body = ['id' => $reviewEntity['id']];
            $modifyReviewFormResponse->status = 200;
            $modifyReviewFormResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $modifyReviewFormResponse->body = $e->getErrorBody();
            $modifyReviewFormResponse->status = $e->statusCode;
            $modifyReviewFormResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, array $reviewEntity, int $visitId, string $studyName, array $visitContext)
    {
        $formOwner = $reviewEntity['user_id'];
        $formValidated = $reviewEntity['validated'];
        $reviewAvailability = $visitContext['review_status']['review_available'];

        //Asked edition review should be owned by current user, not yet validated and in a visit still allowing review
        if ($currentUserId !== $formOwner || $formValidated || !$reviewAvailability) {
            throw new GaelOForbiddenException();
        }
        //Check role reviewer is still available for this user (even if it own the form, his role could have been removed)
        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);
        $this->authorizationVisitService->setVisitContext($visitContext);
        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_REVIEWER)) {
            throw new GaelOForbiddenException();
        }
    }
}
