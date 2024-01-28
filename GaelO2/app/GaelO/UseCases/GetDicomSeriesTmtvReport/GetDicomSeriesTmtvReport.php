<?php

namespace App\GaelO\UseCases\GetDicomSeriesTmtvReport;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Repositories\DicomSeriesRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FileCacheService;
use Exception;

class GetDicomSeriesTmtvReport
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


    public function execute(GetDicomSeriesTmtvReportRequest $getDicomSeriesTmtvReportRequest, GetDicomSeriesTmtvReportResponse $getDicomSeriesTmtvReportResponse)
    {

        try {
            $curentUserId = $getDicomSeriesTmtvReportRequest->currentUserId;
            $role = $getDicomSeriesTmtvReportRequest->role;
            $seriesInstanceUID = $getDicomSeriesTmtvReportRequest->seriesInstanceUID;
            $studyName = $getDicomSeriesTmtvReportRequest->studyName;
            $type = $getDicomSeriesTmtvReportRequest->type;
            $methodology = $getDicomSeriesTmtvReportRequest->methodology;

            $seriesData = $this->dicomSeriesRepository->getSeries($seriesInstanceUID, false);
            $visitId = $seriesData['dicom_study']['visit_id'];

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);

            $this->checkAuthorization($curentUserId, $role, $visitId, $studyName, $visitContext);

            if ($type === "stats") {
                $previewData = $this->fileCacheService->getTmtvResults($seriesInstanceUID, $methodology);
            } else {
                $previewData = $this->fileCacheService->getTmtvPreview($seriesInstanceUID, $methodology);
            }


            $finfo = finfo_open();
            $mimeType = finfo_buffer($finfo, $previewData, FILEINFO_MIME_TYPE);
            finfo_close($finfo);

            $getDicomSeriesTmtvReportResponse->body = $previewData;
            $getDicomSeriesTmtvReportResponse->status = 200;
            $getDicomSeriesTmtvReportResponse->statusText = 'OK';
            $getDicomSeriesTmtvReportResponse->contentType = $mimeType;
        } catch (AbstractGaelOException $e) {
            $getDicomSeriesTmtvReportResponse->body = $e->getErrorBody();
            $getDicomSeriesTmtvReportResponse->status = $e->statusCode;
            $getDicomSeriesTmtvReportResponse->statusText = $e->statusText;
            $getDicomSeriesTmtvReportResponse->contentType = "application/json";
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
