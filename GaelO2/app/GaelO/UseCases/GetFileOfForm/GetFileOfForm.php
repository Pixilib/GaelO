<?php

namespace App\GaelO\UseCases\GetFileOfForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationReviewService;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use Exception;

class GetFileOfForm
{

    private AuthorizationVisitService $authorizationVisitService;
    private AuthorizationReviewService $authorizationReviewService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(AuthorizationVisitService $authorizationVisitService, AuthorizationReviewService $authorizationReviewService, ReviewRepositoryInterface $reviewRepositoryInterface, FrameworkInterface $frameworkInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->authorizationReviewService = $authorizationReviewService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(GetFileOfFormRequest $getFileOfFormRequest, GetFileOfFormResponse $getFileOfFormResponse)
    {

        try {

            $reviewId = $getFileOfFormRequest->id;
            $role = $getFileOfFormRequest->role;
            $currentUserId = $getFileOfFormRequest->currentUserId;

            $reviewEntity = $this->reviewRepositoryInterface->find($getFileOfFormRequest->id);

            $local = $reviewEntity['local'];
            $studyName = $reviewEntity['study_name'];
            $visitId = $reviewEntity['visit_id'];
            $this->checkAuthorization($local, $reviewId, $visitId, $currentUserId, $role, $studyName);

            $getFileOfFormResponse->status = 200;
            $getFileOfFormResponse->statusText = 'OK';
            $getFileOfFormResponse->filePath = $reviewEntity['sent_files'][$getFileOfFormRequest->key];
            $getFileOfFormResponse->filename = basename($reviewEntity['sent_files'][$getFileOfFormRequest->key]);
        } catch (AbstractGaelOException $e) {

            $getFileOfFormResponse->status = $e->statusCode;
            $getFileOfFormResponse->statusText = $e->statusText;
            $getFileOfFormResponse->body = $e->getErrorBody();
        } catch (Exception $e) {

            throw $e;
        }
    }

    private function checkAuthorization(bool $local, int $reviewId, int $visitId, int $currentUserId, string $role, string $studyName): void
    {
        if ($local) {
            $this->authorizationVisitService->setVisitId($visitId);
            $this->authorizationVisitService->setUserId($currentUserId);
            $this->authorizationVisitService->setStudyName($studyName);
            if (!$this->authorizationVisitService->isVisitAllowed($role)) throw new GaelOForbiddenException();
        } else {
            $this->authorizationReviewService->setReviewId($reviewId);
            $this->authorizationReviewService->setUserId($currentUserId);
            if (!$this->authorizationReviewService->isReviewAllowed(Constants::ROLE_SUPERVISOR)) throw new GaelOForbiddenException();
        }
    }
}
