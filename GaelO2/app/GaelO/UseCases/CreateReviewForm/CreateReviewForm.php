<?php

namespace App\GaelO\UseCases\CreateReviewForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\ReviewStatusEnum;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FormService\ReviewFormService;
use Exception;

class CreateReviewForm
{

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private ReviewFormService $reviewFormService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;

    public function __construct(
        TrackerRepositoryInterface $trackerRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        ReviewFormService $reviewFormService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        AuthorizationVisitService $authorizationVisitService
    ) {
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->reviewFormService = $reviewFormService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
    }

    public function execute(CreateReviewFormRequest $createReviewFormRequest, CreateReviewFormResponse $createReviewFormResponse)
    {

        try {

            if (!isset($createReviewFormRequest->validated) || !isset($createReviewFormRequest->visitId)) {
                throw new GaelOBadRequestException('VisitID and Validated Status are mandatory');
            }

            $visitId = $createReviewFormRequest->visitId;
            $studyName = $createReviewFormRequest->studyName;
            $currentUserId = $createReviewFormRequest->currentUserId;
            $formData = $createReviewFormRequest->data;
            $validated = $createReviewFormRequest->validated;
            $adjudication = $createReviewFormRequest->adjudication;

            if ($this->reviewRepositoryInterface->isExistingReviewForStudyVisitUser($studyName, $visitId, $currentUserId)) {
                throw new GaelOConflictException('Review Already Created');
            }

            $visitContext = $this->visitRepositoryInterface->getVisitWithContextAndReviewStatus($visitId, $studyName);

            $reviewStatus = $visitContext['review_status']['review_status'];
            $reviewAvailable = $visitContext['review_status']['review_available'];

            if ($adjudication &&  $reviewStatus !== ReviewStatusEnum::WAIT_ADJUDICATION->value) {
                throw new GaelOBadRequestException('Review Not Awaiting Adjudication');
            }

            $this->checkAuthorization($visitId, $currentUserId, $studyName, $visitContext);

            //Call service to register form
            $this->reviewFormService->setCurrentUserId($currentUserId);
            $this->reviewFormService->setVisitContextAndStudy($visitContext, $studyName);
            $createdReviewId = $this->reviewFormService->createForm($formData, $validated, $adjudication);

            //Write in Tracker
            $actionDetails = [
                'review_id' => $createdReviewId,
                'adjudication' => $adjudication,
                'raw_data' => $formData,
                'validated' => $validated
            ];

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::ROLE_REVIEWER, $studyName, $visitId, Constants::TRACKER_SAVE_REVIEWER_FORM, $actionDetails);

            $createReviewFormResponse->body = ['id' => $createdReviewId];
            $createReviewFormResponse->status = 201;
            $createReviewFormResponse->statusText =  'Created';
        } catch (AbstractGaelOException $e) {

            $createReviewFormResponse->body = $e->getErrorBody();
            $createReviewFormResponse->status = $e->statusCode;
            $createReviewFormResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $visitId, int $currentUserId, string $studyName, array $visitContext)
    {
        $reviewAvailability = $visitContext['review_status']['review_available'];
        if (!$reviewAvailability) {
            throw new GaelOForbiddenException();
        }

        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);
        $this->authorizationVisitService->setVisitContext($visitContext);

        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_REVIEWER)) {
            throw new GaelOForbiddenException();
        }
    }
}
