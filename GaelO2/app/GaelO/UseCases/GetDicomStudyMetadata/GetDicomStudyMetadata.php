<?php

namespace App\GaelO\UseCases\GetDicomStudyMetadata;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FileCacheService;
use Exception;

class GetDicomStudyMetadata
{

    private AuthorizationVisitService $authorizationVisitService;
    private FileCacheService $fileCacheService;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;

    public function __construct(AuthorizationVisitService $authorizationVisitService, DicomStudyRepositoryInterface $dicomStudyRepositoryInterface, VisitRepositoryInterface $visitRepositoryInterface, FileCacheService $fileCacheService)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->fileCacheService = $fileCacheService;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function execute(GetDicomStudyMetadataRequest $getDicomStudyMetadataRequest, GetDicomStudyMetadataResponse $getDicomStudyMetadataResponse)
    {

        try {
            $curentUserId = $getDicomStudyMetadataRequest->currentUserId;
            $role = $getDicomStudyMetadataRequest->role;
            $studyInstanceUID = $getDicomStudyMetadataRequest->studyInstanceUID;

            $studyData = $this->dicomStudyRepositoryInterface->getDicomStudy($studyInstanceUID, false);
            $visitId = $studyData['visit_id'];

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
            $studyName = $visitContext['patient']['study_name'];

            $this->checkAuthorization($curentUserId, $role, $visitId, $studyName, $visitContext);

            $studyMetadata = $this->fileCacheService->getDicomMetadata($studyInstanceUID);

            $getDicomStudyMetadataResponse->body = json_decode($studyMetadata);
            $getDicomStudyMetadataResponse->status = 200;
            $getDicomStudyMetadataResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getDicomStudyMetadataResponse->body = $e->getErrorBody();
            $getDicomStudyMetadataResponse->status = $e->statusCode;
            $getDicomStudyMetadataResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $role, int $visitId, string $studyName, array $visitContext): void
    {
        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);
        $this->authorizationVisitService->setVisitContext($visitContext);

        if (!$this->authorizationVisitService->isVisitAllowed($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
