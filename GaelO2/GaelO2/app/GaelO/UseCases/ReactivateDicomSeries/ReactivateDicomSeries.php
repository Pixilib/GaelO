<?php

namespace App\GaelO\UseCases\ReactivateDicomSeries;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\DicomSeriesService;
use App\GaelO\Services\TrackerService;
use Exception;

class ReactivateDicomSeries{

    private AuthorizationVisitService $authorizationVisitService;
    private DicomSeriesService $dicomSeriesService;
    private TrackerService $trackerService;

    public function __construct(PersistenceInterface $persistenceInterface,
                                DicomSeriesService $dicomSeriesService,
                                AuthorizationVisitService $authorizationVisitService,
                                TrackerService $trackerService)
    {
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationVisitService = $authorizationVisitService;
        $this->dicomSeriesService = $dicomSeriesService;
        $this->trackerService = $trackerService;

    }

    public function execute(ReactivateDicomSeriesRequest $reactivateDicomSeriesRequest, ReactivateDicomSeriesResponse $reactivateDicomSeriesResponse){

        try{

            $seriesData = $this->dicomSeriesService->getSeriesBySeriesInstanceUID($reactivateDicomSeriesRequest->seriesInstanceUID, true);

            if($seriesData['orthanc_study'] === null){
                throw new GaelOBadRequestException("Parent study is deactivated can't act on child series");
            }

            $visitId = $seriesData['orthanc_study']['visit_id'];
            $visitContext = $this->persistenceInterface->getVisitContext($visitId);

            $this->checkAuthorization($reactivateDicomSeriesRequest->currentUserId, $visitId, $visitContext['state_quality_control']);

            $this->dicomSeriesService->reactivateBySeriesInstanceUID($reactivateDicomSeriesRequest->seriesInstanceUID);

            $studyName = $visitContext['visit_type']['visit_group']['study_name'];

            $actionDetails = [
                'seriesInstanceUID'=>$seriesData['series_uid'],
            ];

            $this->trackerService->writeAction(
                $reactivateDicomSeriesRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $visitId,
                Constants::TRACKER_REACTIVATE_DICOM_SERIES,
                $actionDetails
            );

            $reactivateDicomSeriesResponse->status = 200;
            $reactivateDicomSeriesResponse->statusText =  'OK';


        } catch (GaelOException $e){

            $reactivateDicomSeriesResponse->status = $e->statusCode;
            $reactivateDicomSeriesResponse->statusText = $e->statusText;
            $reactivateDicomSeriesResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, int $visitId, string $qcStatus) : void{

        //If QC is done, can't reactivate series
        if( in_array($qcStatus, [Constants::QUALITY_CONTROL_ACCEPTED, Constants::QUALITY_CONSTROL_REFUSED])){
            throw new GaelOForbiddenException();
        }

        $this->authorizationVisitService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        $this->authorizationVisitService->setVisitId($visitId);

        if ( ! $this->authorizationVisitService->isVisitAllowed()){
            throw new GaelOForbiddenException();
        }

    }

}
