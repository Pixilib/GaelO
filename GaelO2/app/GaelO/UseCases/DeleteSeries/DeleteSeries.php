<?php

namespace App\GaelO\UseCases\DeleteSeries;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\DicomService;
use Exception;

class DeleteSeries
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private DicomService $dicomService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;


    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface, DicomService $dicomService, AuthorizationVisitService $authorizationVisitService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->dicomService = $dicomService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function execute(DeleteSeriesRequest $deleteSeriesRequest, DeleteSeriesResponse $deleteSeriesResponse)
    {

        try {

            if (empty($deleteSeriesRequest->reason)) {
                throw new GaelOBadRequestException("A reason must be specified");
            }

            $seriesInstanceUID = $deleteSeriesRequest->seriesInstanceUID;
            $currentUserId = $deleteSeriesRequest->currentUserId;
            $role = $deleteSeriesRequest->role;
            $reason = $deleteSeriesRequest->reason;

            $seriesData = $this->dicomSeriesRepositoryInterface->getSeries($seriesInstanceUID, false);
            $visitId = $seriesData['dicom_study']['visit_id'];

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
            $stateQc = $visitContext['state_quality_control'];
            $studyName = $visitContext['patient']['study_name'];

            $this->checkAuthorization($currentUserId, $visitId, $role, $stateQc, $studyName);

            $this->dicomService->deleteSeries($seriesInstanceUID, $role);

            $actionDetails = [
                'seriesInstanceUID' => $seriesData['series_uid'],
                'reason' => $reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                $role,
                $studyName,
                $visitId,
                Constants::TRACKER_DELETE_DICOM_SERIES,
                $actionDetails
            );

            $deleteSeriesResponse->status = 200;
            $deleteSeriesResponse->statusText =  'OK';

        } catch (GaelOException $e) {
            $deleteSeriesResponse->body = $e->getErrorBody();
            $deleteSeriesResponse->status = $e->statusCode;
            $deleteSeriesResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function checkAuthorization(int $userId, int $visitId, string $role, string $qcStatus, string $studyName): void
    {

        //Series delete only for Investigator, Controller, Supervisor
        if (!in_array($role, [Constants::ROLE_INVESTIGATOR, Constants::ROLE_CONTROLLER, Constants::ROLE_SUPERVISOR])) {
            throw new GaelOForbiddenException();
        }

        //If QC is done, can't remove series
        if (in_array($qcStatus, [Constants::QUALITY_CONTROL_ACCEPTED, Constants::QUALITY_CONTROL_REFUSED, CONSTANTS::QUALITY_CONTROL_NOT_NEEDED])) {
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
