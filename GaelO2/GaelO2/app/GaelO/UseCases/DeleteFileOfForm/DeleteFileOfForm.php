<?php

namespace App\GaelO\UseCases\DeleteFileOfForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\FormService;
use Exception;

class DeleteFileOfForm
{

    private AuthorizationVisitService $authorizationVisitService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private FormService $formService;

    public function __construct(AuthorizationVisitService $authorizationVisitService,
        FormService $formService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
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
            $userId = $reviewEntity['user_id'];
            $local = $reviewEntity['local'];
            $visitId = $reviewEntity['visit_id'];
            $this->checkAuthorization($local, $reviewEntity['validated'], $userId, $visitId, $deleteFileOfFormRequest->currentUserId);

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

    private function checkAuthorization(bool $local, bool $validated, int $reviewOwner, int $visitId, int $currentUserId): void
    {
        if ($validated) throw new GaelOForbiddenException("Form Already Validated");
        $this->authorizationVisitService->setVisitId($visitId);
        if ($local) {
            $this->authorizationVisitService->setCurrentUserAndRole($currentUserId, Constants::ROLE_INVESTIGATOR);
            if (!$this->authorizationVisitService->isVisitAllowed()) throw new GaelOForbiddenException();
        } else {
            $this->authorizationVisitService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
            if (!$this->authorizationVisitService->isVisitAllowed()) throw new GaelOForbiddenException();
            if ($reviewOwner !== $currentUserId) throw new GaelOForbiddenException("Only form owner can add files");
        }
    }
}
