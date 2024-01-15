<?php

namespace App\GaelO\UseCases\GetDicomSeriesMetadata;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Repositories\DicomSeriesRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FileCacheService;
use Exception;
use Illuminate\Support\Facades\Log;

class GetDicomSeriesMetadata
{

    private AuthorizationVisitService $authorizationVisitService;
    private FileCacheService $fileCacheService;
    private DicomSeriesRepository $dicomSeriesRepository;
    private VisitRepositoryInterface $visitRepositoryInterface;

    public function __construct(AuthorizationVisitService $authorizationVisitService, DicomSeriesRepository $dicomSeriesRepository, VisitRepositoryInterface $visitRepositoryInterface, FileCacheService $fileCacheService)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->fileCacheService = $fileCacheService;
        $this->dicomSeriesRepository = $dicomSeriesRepository;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function execute(GetDicomSeriesMetadataRequest $getDicomSeriesMetadataRequest, GetDicomSeriesMetadataResponse $getDicomSeriesMetadataResponse)
    {

        try {
            $curentUserId = $getDicomSeriesMetadataRequest->currentUserId;
            $role = $getDicomSeriesMetadataRequest->role;
            $seriesInstanceUID = $getDicomSeriesMetadataRequest->seriesInstanceUID;
            $studyName = $getDicomSeriesMetadataRequest->studyName;

            $seriesData = $this->dicomSeriesRepository->getSeries($seriesInstanceUID, false);
            $visitId = $seriesData['dicom_study']['visit_id'];

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);

            $this->checkAuthorization($curentUserId, $role, $visitId, $studyName, $visitContext);

            $studyMetadata = $this->fileCacheService->getDicomMetadata($seriesInstanceUID);

            $getDicomSeriesMetadataResponse->body = json_decode($studyMetadata);
            $getDicomSeriesMetadataResponse->status = 200;
            $getDicomSeriesMetadataResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getDicomSeriesMetadataResponse->body = $e->getErrorBody();
            $getDicomSeriesMetadataResponse->status = $e->statusCode;
            $getDicomSeriesMetadataResponse->statusText = $e->statusText;
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
