<?php

namespace App\GaelO\UseCases\CreateReviewForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FormService\ReviewFormService;
use Exception;

class CreateReview
{

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private ReviewFormService $reviewFormService;
    private ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;

    public function __construct(
        TrackerRepositoryInterface $trackerRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        ReviewFormService $reviewFormService,
        ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        AuthorizationVisitService $authorizationVisitService
    ) {
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->reviewFormService = $reviewFormService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
    }

    public function execute(CreateReviewFormRequest $createReviewFormRequest, CreateReviewFormResponse $createReviewFormResponse)
    {

        try {

            if (!isset($createReviewFormRequest->validated) || !isset($createReviewFormRequest->visitId)) {
                throw new GaelOBadRequestException('VisitID and Validated Status are mandatory');
            }

            if ($this->reviewRepositoryInterface->isExistingReviewForStudyVisitUser($createReviewFormRequest->studyName, $createReviewFormRequest->visitId, $createReviewFormRequest->currentUserId)) {
                throw new GaelOConflictException('Review Already Created');
            };

            $visitContext = $this->visitRepositoryInterface->getVisitContext($createReviewFormRequest->visitId);
            $reviewStatus = $this->reviewStatusRepositoryInterface->getReviewStatus($createReviewFormRequest->visitId, $createReviewFormRequest->studyName);

            if ($createReviewFormRequest->adjudication &&  $reviewStatus['review_status'] !== Constants::REVIEW_STATUS_WAIT_ADJUDICATION) {
                throw new GaelOBadRequestException('Review Not Awaiting Adjudication');
            };

            $this->checkAuthorization($createReviewFormRequest->visitId, $createReviewFormRequest->currentUserId, $reviewStatus['review_available'], $createReviewFormRequest->studyName);

            //Call service to register form
            $this->reviewFormService->setCurrentUserId($createReviewFormRequest->currentUserId);
            $this->reviewFormService->setReviewStatus($reviewStatus);
            $this->reviewFormService->setVisitContextAndStudy($visitContext, $createReviewFormRequest->studyName);
            $createdReviewId = $this->reviewFormService->saveReview($createReviewFormRequest->data, $createReviewFormRequest->validated, $createReviewFormRequest->adjudication);

            //Write in Tracker
            $actionDetails = [
                'idReview' => $createdReviewId,
                'adjudication' => $reviewStatus['review_status'] === Constants::REVIEW_STATUS_WAIT_ADJUDICATION,
                'raw_data' => $createReviewFormRequest->data,
                'validated' => $createReviewFormRequest->validated
            ];

            $this->trackerRepositoryInterface->writeAction($createReviewFormRequest->currentUserId, Constants::ROLE_REVIEWER, $createReviewFormRequest->studyName, $createReviewFormRequest->visitId, Constants::TRACKER_SAVE_REVIEWER_FORM, $actionDetails);

            $createReviewFormResponse->status = 201;
            $createReviewFormResponse->statusText =  'Created';
        } catch (GaelOException $e) {

            $createReviewFormResponse->body = $e->getErrorBody();
            $createReviewFormResponse->status = $e->statusCode;
            $createReviewFormResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $visitId, int $currentUserId, bool $reviewAvailability, string $studyName)
    {

        if (!$reviewAvailability) {
            throw new GaelOForbiddenException();
        }

        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);

        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_REVIEWER)) {
            throw new GaelOForbiddenException();
        };
    }
}
