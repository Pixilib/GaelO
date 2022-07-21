<?php

namespace App\GaelO\UseCases\ReactivateDicomSeries;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use Exception;

class ReactivateDicomSeries
{
    private AuthorizationVisitService $authorizationVisitService;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;

    public function __construct(
        VisitRepositoryInterface $visitRepositoryInterface,
        DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface,
        AuthorizationVisitService $authorizationVisitService,
        TrackerRepositoryInterface $trackerRepositoryInterface
    ) {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ReactivateDicomSeriesRequest $reactivateDicomSeriesRequest, ReactivateDicomSeriesResponse $reactivateDicomSeriesResponse)
    {

        try {

            if (empty($reactivateDicomSeriesRequest->reason)) throw new GaelOBadRequestException('Reason must be specified');

            $seriesInstanceUID = $reactivateDicomSeriesRequest->seriesInstanceUID;
            $reason = $reactivateDicomSeriesRequest->reason;
            $currentUserId = $reactivateDicomSeriesRequest->currentUserId;
            $role = $reactivateDicomSeriesRequest->role;

            $seriesData = $this->dicomSeriesRepositoryInterface->getSeries($seriesInstanceUID, true);

            if ($seriesData['dicom_study'] === null) {
                throw new GaelOBadRequestException("Parent study is deactivated can't act on child series");
            }

            $visitId = $seriesData['dicom_study']['visit_id'];
            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
            $studyName = $visitContext['patient']['study_name'];

            $this->checkAuthorization($currentUserId, $visitId, $visitContext['state_quality_control'], $role, $studyName);

            $this->dicomSeriesRepositoryInterface->reactivateSeries($seriesInstanceUID);

            $actionDetails = [
                'seriesInstanceUID' => $seriesData['series_uid'],
                'reason' => $reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                $role,
                $studyName,
                $visitId,
                Constants::TRACKER_REACTIVATE_DICOM_SERIES,
                $actionDetails
            );

            $reactivateDicomSeriesResponse->status = 200;
            $reactivateDicomSeriesResponse->statusText =  'OK';
        } catch (GaelOException $e) {
            $reactivateDicomSeriesResponse->status = $e->statusCode;
            $reactivateDicomSeriesResponse->statusText = $e->statusText;
            $reactivateDicomSeriesResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, int $visitId, string $qcStatus, string $role, string $studyName): void
    {

        //If QC is done, can't reactivate series
        if (in_array($qcStatus, [Constants::QUALITY_CONTROL_ACCEPTED, Constants::QUALITY_CONTROL_REFUSED, Constants::QUALITY_CONTROL_NOT_NEEDED])) {
            throw new GaelOForbiddenException();
        }

        if (!in_array($role, [Constants::ROLE_INVESTIGATOR, Constants::ROLE_CONTROLLER, Constants::ROLE_SUPERVISOR])) {
            throw new GaelOForbiddenException();
        }

        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);

        if (!$this->authorizationVisitService->isVisitAllowed($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
