<?php

namespace App\GaelO\UseCases\DeleteSeries;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Constants\Enums\ReviewStatusEnum;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\Repositories\ReviewStatusRepositoryInterface;
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
    private ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;


    public function __construct(
        VisitRepositoryInterface $visitRepositoryInterface,
        DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface,
        ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface,
        DicomService $dicomService,
        AuthorizationVisitService $authorizationVisitService,
        TrackerRepositoryInterface $trackerRepositoryInterface
    ) {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->dicomService = $dicomService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
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

            //For Controller, forbid series deletion if it's the last one
            $studyData = $this->dicomSeriesRepositoryInterface->getDicomSeriesOfStudyInstanceUIDArray([$seriesData['study_instance_uid']], false);
            if ($role === Constants::ROLE_CONTROLLER && sizeof($studyData) === 1) {
                throw new GaelOForbiddenException("You cannot reset DICOM upload");
            }

            $visitId = $seriesData['dicom_study']['visit_id'];

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
            $studyName = $visitContext['patient']['study_name'];

            if ($deleteSeriesRequest->studyName !== $studyName) {
                throw new GaelOForbiddenException();
            }

            $this->checkAuthorization($currentUserId, $visitId, $role, $studyName, $visitContext);

            $this->dicomService->setCurrentUserId($currentUserId);
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
        } catch (AbstractGaelOException $e) {
            $deleteSeriesResponse->body = $e->getErrorBody();
            $deleteSeriesResponse->status = $e->statusCode;
            $deleteSeriesResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function checkAuthorization(int $userId, int $visitId, string $role, string $studyName, array $visitContext): void
    {
        $qcStatus = $visitContext['state_quality_control'];
        //Series delete only for Investigator, Controller, Supervisor
        if (!in_array($role, [Constants::ROLE_INVESTIGATOR, Constants::ROLE_CONTROLLER, Constants::ROLE_SUPERVISOR])) {
            throw new GaelOForbiddenException();
        }

        //If QC is done, can't remove series
        if (in_array($qcStatus, [QualityControlStateEnum::ACCEPTED->value, QualityControlStateEnum::REFUSED->value])) {
            throw new GaelOForbiddenException();
        }

        //If review started can't remove series
        $reviewStatusEntity = $this->reviewStatusRepositoryInterface->getReviewStatus($visitId, $studyName);
        if (!in_array($reviewStatusEntity['review_status'], array(ReviewStatusEnum::NOT_DONE->value, ReviewStatusEnum::NOT_NEEDED->value))) {
            throw new GaelOForbiddenException("Delete Review first to remove series");
        }

        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);

        if (!$this->authorizationVisitService->isVisitAllowed($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
