<?php

namespace App\GaelO\UseCases\DeleteFileOfForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationReviewService;
use App\GaelO\Services\FormService\FormService;
use Exception;

class DeleteFileOfForm
{

    private AuthorizationReviewService $authorizationReviewService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private FormService $formService;

    public function __construct(AuthorizationReviewService $authorizationReviewService,
        FormService $formService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface)
    {
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

            $studyName = $reviewEntity['study_name'];
            $local = $reviewEntity['local'];
            $this->checkAuthorization($local, $reviewEntity['validated'], $deleteFileOfFormRequest->id, $deleteFileOfFormRequest->currentUserId);

            $visitContext = $this->visitRepositoryInterface->getVisitContext($reviewEntity['visit_id']);
            $this->formService->setVisitContextAndStudy($visitContext, $studyName);
            $this->formService->removeFile($reviewEntity, $deleteFileOfFormRequest->key);

            $actionDetails = [
                'removed_file' => $deleteFileOfFormRequest->key,
                'review_id' => $reviewEntity['id']
            ];

            $this->trackerRepositoryInterface->writeAction(
                $deleteFileOfFormRequest->currentUserId,
                $reviewEntity['local'] ? Constants::ROLE_INVESTIGATOR : Constants::ROLE_SUPERVISOR,
                $studyName,
                $reviewEntity['visit_id'],
                $reviewEntity['local'] ? Constants::TRACKER_SAVE_INVESTIGATOR_FORM : Constants::TRACKER_SAVE_REVIEWER_FORM,
                $actionDetails
            );

            $deleteFileOfFormResponse->status = 200;
            $deleteFileOfFormResponse->statusText =  'OK';

        } catch (GaelOException $e) {

            $deleteFileOfFormResponse->body = $e->getErrorBody();
            $deleteFileOfFormResponse->status = $e->statusCode;
            $deleteFileOfFormResponse->statusText =  $e->statusText;

        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(bool $local, bool $validated, int $reviewId, int $currentUserId): void
    {
        if ($validated) throw new GaelOForbiddenException("Form Already Validated");
        $this->authorizationReviewService->setUserId($currentUserId);
        $this->authorizationReviewService->setReviewId($reviewId);

        //Required role depends on local or review form
        $role = $local ? Constants::ROLE_INVESTIGATOR : Constants::ROLE_REVIEWER;
        if ( !$this->authorizationReviewService->isReviewAllowed($role) ) throw new GaelOForbiddenException();

    }
}
