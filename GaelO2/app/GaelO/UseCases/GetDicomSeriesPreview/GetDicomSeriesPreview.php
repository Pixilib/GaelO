<?php

namespace App\GaelO\UseCases\GetDicomSeriesPreview;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Repositories\DicomSeriesRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FileCacheService;
use Exception;

class GetDicomSeriesPreview
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

    public function execute(GetDicomSeriesPreviewRequest $getDicomSeriesPreviewRequest, GetDicomSeriesPreviewResponse $getDicomSeriesPreviewResponse)
    {

        try {
            $curentUserId = $getDicomSeriesPreviewRequest->currentUserId;
            $role = $getDicomSeriesPreviewRequest->role;
            $seriesInstanceUID = $getDicomSeriesPreviewRequest->seriesInstanceUID;
            $index = $getDicomSeriesPreviewRequest->index;

            $seriesData = $this->dicomSeriesRepository->getSeries($seriesInstanceUID, false);
            $visitId = $seriesData['dicom_study']['visit_id'];

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
            $studyName = $visitContext['patient']['study_name'];

            $this->checkAuthorization($curentUserId, $role, $visitId, $studyName, $visitContext);

            $previewData = $this->fileCacheService->getSeriesPreview($seriesInstanceUID, $index);
            $finfo = finfo_open();
            $mimeType = finfo_buffer($finfo, $previewData, FILEINFO_MIME_TYPE);
            finfo_close($finfo);

            $getDicomSeriesPreviewResponse->body = $previewData;
            $getDicomSeriesPreviewResponse->status = 200;
            $getDicomSeriesPreviewResponse->statusText = 'OK';
            $getDicomSeriesPreviewResponse->contentType = $mimeType;
        } catch (AbstractGaelOException $e) {
            $getDicomSeriesPreviewResponse->body = $e->getErrorBody();
            $getDicomSeriesPreviewResponse->status = $e->statusCode;
            $getDicomSeriesPreviewResponse->statusText = $e->statusText;
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
