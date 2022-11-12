<?php

namespace App\GaelO\UseCases\DeleteFileOfForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationReviewService;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FormService\FormService;
use Exception;

class DeleteFileOfForm
{

    private AuthorizationVisitService $authorizationVisitService;
    private AuthorizationReviewService $authorizationReviewService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private FormService $formService;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService,
        AuthorizationReviewService $authorizationReviewService,
        FormService $formService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface
    ) {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->authorizationReviewService = $authorizationReviewService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->formService = $formService;
    }

    public function execute(DeleteFileOfFormRequest $deleteFileOfFormRequest, DeleteFileOfFormResponse $deleteFileOfFormResponse)
    {
        try {

            $reviewEntity = $this->reviewRepositoryInterface->find($deleteFileOfFormRequest->id);

            $currentUserId = $deleteFileOfFormRequest->currentUserId;
            $fileKey = $deleteFileOfFormRequest->key;
            $studyName = $reviewEntity['study_name'];
            $local = $reviewEntity['local'];
            $validated = $reviewEntity['validated'];
            $visitId = $reviewEntity['visit_id'];
            $reviewId = $reviewEntity['id'];

            $visitContext = $this->visitRepositoryInterface->getVisitWithContextAndReviewStatus($visitId, $studyName);

            $this->checkAuthorization($local, $validated, $reviewId, $currentUserId, $visitContext);
            $this->formService->setVisitContextAndStudy($visitContext, $studyName);
            $this->formService->removeFile($reviewEntity, $fileKey);

            $actionDetails = [
                'removed_file' => $fileKey,
                'review_id' => $reviewId
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                $local ? Constants::ROLE_INVESTIGATOR : Constants::ROLE_SUPERVISOR,
                $studyName,
                $visitId,
                $local ? Constants::TRACKER_SAVE_INVESTIGATOR_FORM : Constants::TRACKER_SAVE_REVIEWER_FORM,
                $actionDetails
            );

            $deleteFileOfFormResponse->status = 200;
            $deleteFileOfFormResponse->statusText =  'OK';
        } catch (AbstractGaelOException $e) {

            $deleteFileOfFormResponse->body = $e->getErrorBody();
            $deleteFileOfFormResponse->status = $e->statusCode;
            $deleteFileOfFormResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(bool $local, bool $validated, int $reviewId, int $currentUserId, array $visitContext): void
    {
        if ($validated) throw new GaelOForbiddenException("Form Already Validated");


        //Required role depends on local or review form
        if($local){
            $this->authorizationVisitService->setUserId($currentUserId);
            $this->authorizationVisitService->setVisitContext($visitContext);
            $this->authorizationVisitService->setStudyName($visitContext['patient']['study_name']);
            if( ! $this->authorizationVisitService->isVisitAllowed(Constants::ROLE_INVESTIGATOR)) throw new GaelOForbiddenException();
        }else{
            $this->authorizationReviewService->setUserId($currentUserId);
            $this->authorizationReviewService->setReviewId($reviewId);
            if (!$this->authorizationReviewService->isReviewAllowed(Constants::ROLE_REVIEWER)) throw new GaelOForbiddenException();
        }
    }
}
