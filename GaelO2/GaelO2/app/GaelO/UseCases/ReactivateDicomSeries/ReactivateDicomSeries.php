<?php

namespace App\GaelO\UseCases\ReactivateDicomSeries;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use Exception;

class ReactivateDicomSeries{

    private AuthorizationVisitService $authorizationVisitService;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface,
                                DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface,
                                AuthorizationVisitService $authorizationVisitService,
                                TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;

    }

    public function execute(ReactivateDicomSeriesRequest $reactivateDicomSeriesRequest, ReactivateDicomSeriesResponse $reactivateDicomSeriesResponse){

        try{

            if( empty($reactivateDicomSeriesRequest->reason) ) throw new GaelOBadRequestException('Reason must be specified');

            $seriesData = $this->dicomSeriesRepositoryInterface->getSeries($reactivateDicomSeriesRequest->seriesInstanceUID, true);

            if($seriesData['dicom_study'] === null){
                throw new GaelOBadRequestException("Parent study is deactivated can't act on child series");
            }

            $visitId = $seriesData['dicom_study']['visit_id'];
            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);

            $this->checkAuthorization($reactivateDicomSeriesRequest->currentUserId, $visitId, $visitContext['state_quality_control']);

            $this->dicomSeriesRepositoryInterface->reactivateSeries($reactivateDicomSeriesRequest->seriesInstanceUID);

            $studyName = $visitContext['visit_type']['visit_group']['study_name'];

            $actionDetails = [
                'seriesInstanceUID'=>$seriesData['series_uid'],
                'reason' => $reactivateDicomSeriesRequest->reason
            ];

            $this->trackerRepositoryInterface->writeAction(
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
        if( in_array($qcStatus, [Constants::QUALITY_CONTROL_ACCEPTED, Constants::QUALITY_CONTROL_REFUSED])){
            throw new GaelOForbiddenException();
        }

        $this->authorizationVisitService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        $this->authorizationVisitService->setVisitId($visitId);

        if ( ! $this->authorizationVisitService->isVisitAllowed()){
            throw new GaelOForbiddenException();
        }

    }

}
