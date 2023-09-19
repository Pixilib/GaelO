<?php

namespace App\GaelO\UseCases\DeleteFileOfVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\VisitService;
use Exception;

class DeleteFileOfVisit
{

    private AuthorizationVisitService $authorizationVisitService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private VisitService $visitService;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService,
        VisitService $visitService,
        TrackerRepositoryInterface $trackerRepositoryInterface,
    ) {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->visitService = $visitService;
    }

    public function execute(DeleteFileOfVisitRequest $deleteFileOfVisitRequest, DeleteFileOfVisitResponse $deleteFileOfVisitResponse)
    {
        try {

            $studyName = $deleteFileOfVisitRequest->studyName;
            $visitId = $deleteFileOfVisitRequest->visitId;
            $role = $deleteFileOfVisitRequest->role;
            $currentUserId = $deleteFileOfVisitRequest->currentUserId;
            $fileKey = $deleteFileOfVisitRequest->key;

            if ($role !== Constants::ROLE_SUPERVISOR) {
                throw new GaelOForbiddenException("Supervisor role only");
            }

            $this->visitService->setVisitId($visitId);
            $visitContext = $this->visitService->getVisitContext();

            if ($deleteFileOfVisitRequest->studyName !== $visitContext['patient']['study_name']) {
                throw new GaelOForbiddenException('Should be called from original study');
            }

            $this->checkAuthorization($visitContext, $currentUserId);

            $this->visitService->removeFile($fileKey);

            $actionDetails = [
                'removed_file' => $fileKey
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $visitId,
                Constants::TRACKER_UPDATE_VISIT_FILE,
                $actionDetails
            );

            $deleteFileOfVisitResponse->status = 200;
            $deleteFileOfVisitResponse->statusText =  'OK';
        } catch (AbstractGaelOException $e) {
            $deleteFileOfVisitResponse->body = $e->getErrorBody();
            $deleteFileOfVisitResponse->status = $e->statusCode;
            $deleteFileOfVisitResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(array $visitContext, int $currentUserId): void
    {
        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setVisitContext($visitContext);
        $this->authorizationVisitService->setStudyName($visitContext['patient']['study_name']);
        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        };
    }
}
