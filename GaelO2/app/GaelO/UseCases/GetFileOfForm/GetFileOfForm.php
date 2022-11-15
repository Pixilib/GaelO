<?php

namespace App\GaelO\UseCases\GetFileOfForm;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use Exception;

class GetFileOfForm
{

    private AuthorizationVisitService $authorizationVisitService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(AuthorizationVisitService $authorizationVisitService, ReviewRepositoryInterface $reviewRepositoryInterface, FrameworkInterface $frameworkInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(GetFileOfFormRequest $getFileOfFormRequest, GetFileOfFormResponse $getFileOfFormResponse)
    {

        try {

            $reviewId = $getFileOfFormRequest->id;
            $fileKey = $getFileOfFormRequest->key;
            $role = $getFileOfFormRequest->role;
            $currentUserId = $getFileOfFormRequest->currentUserId;

            $reviewEntity = $this->reviewRepositoryInterface->find($reviewId);

            $studyName = $reviewEntity['study_name'];
            $visitId = $reviewEntity['visit_id'];
            $this->checkAuthorization($visitId, $currentUserId, $role, $studyName);

            $getFileOfFormResponse->status = 200;
            $getFileOfFormResponse->statusText = 'OK';
            $getFileOfFormResponse->filePath = $reviewEntity['sent_files'][$fileKey];
            $getFileOfFormResponse->filename = basename($reviewEntity['sent_files'][$fileKey]);
        } catch (AbstractGaelOException $e) {

            $getFileOfFormResponse->status = $e->statusCode;
            $getFileOfFormResponse->statusText = $e->statusText;
            $getFileOfFormResponse->body = $e->getErrorBody();
        } catch (Exception $e) {

            throw $e;
        }
    }

    private function checkAuthorization(int $visitId, int $currentUserId, string $role, string $studyName): void
    {
        //Check visit is allowed (a review shall be able to get the associated file of the investigator form)
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setStudyName($studyName);
        if (!$this->authorizationVisitService->isVisitAllowed($role)) throw new GaelOForbiddenException();
    }
}
