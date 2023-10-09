<?php

namespace App\GaelO\UseCases\GetFileOfVisit;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Exceptions\GaelONotFoundException;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use Exception;

class GetFileOfVisit
{
    private AuthorizationVisitService $authorizationVisitService;
    private VisitRepositoryInterface $visitRepositoryInterface;

    public function __construct(AuthorizationVisitService $authorizationVisitService, VisitRepositoryInterface $visitRepositoryInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function execute(GetFileOfVisitRequest $getFileOfVisitRequest, GetFileOfVisitResponse $getFileOfVisitResponse)
    {

        try {
            $visitId = $getFileOfVisitRequest->visitId;
            $fileKey = $getFileOfVisitRequest->key;
            $role = $getFileOfVisitRequest->role;
            $studyName = $getFileOfVisitRequest->studyName;
            $currentUserId = $getFileOfVisitRequest->currentUserId;

            $visitEntity = $this->visitRepositoryInterface->getVisitContext($visitId);

            $this->checkAuthorization($visitId, $currentUserId, $role, $studyName);
            if(!array_key_exists($fileKey, $visitEntity['sent_files'])){
                throw new GaelONotFoundException("File key not found");
            }

            $getFileOfVisitResponse->status = 200;
            $getFileOfVisitResponse->statusText = 'OK';
            $getFileOfVisitResponse->filePath = $visitEntity['sent_files'][$fileKey];
            $getFileOfVisitResponse->filename = basename($visitEntity['sent_files'][$fileKey]);
        } catch (AbstractGaelOException $e) {
            $getFileOfVisitResponse->status = $e->statusCode;
            $getFileOfVisitResponse->statusText = $e->statusText;
            $getFileOfVisitResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $visitId, int $currentUserId, string $role, string $studyName): void
    {
        //Check if visit is allowed
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setStudyName($studyName);
        if (!$this->authorizationVisitService->isVisitAllowed($role)) throw new GaelOForbiddenException();
    }
}
