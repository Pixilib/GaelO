<?php

namespace App\GaelO\UseCases\ReactivateDicomSeries;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\OrthancSeriesRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\DicomSeriesService;
use Exception;

class ReactivateDicomSeries{

    private AuthorizationVisitService $authorizationVisitService;
    private OrthancSeriesRepositoryInterface $orthancSeriesRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface,
                                OrthancSeriesRepositoryInterface $orthancSeriesRepositoryInterface,
                                AuthorizationVisitService $authorizationVisitService,
                                TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
        $this->orthancSeriesRepositoryInterface = $orthancSeriesRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;

    }

    public function execute(ReactivateDicomSeriesRequest $reactivateDicomSeriesRequest, ReactivateDicomSeriesResponse $reactivateDicomSeriesResponse){

        try{

            $seriesData = $this->orthancSeriesRepositoryInterface->getSeriesBySeriesInstanceUID($reactivateDicomSeriesRequest->seriesInstanceUID, true);

            if($seriesData['dicom_study'] === null){
                throw new GaelOBadRequestException("Parent study is deactivated can't act on child series");
            }

            $visitId = $seriesData['dicom_study']['visit_id'];
            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);

            $this->checkAuthorization($reactivateDicomSeriesRequest->currentUserId, $visitId, $visitContext['state_quality_control']);

            $this->orthancSeriesRepositoryInterface->reactivateBySeriesInstanceUID($reactivateDicomSeriesRequest->seriesInstanceUID);

            $studyName = $visitContext['visit_type']['visit_group']['study_name'];

            $actionDetails = [
                'seriesInstanceUID'=>$seriesData['series_uid'],
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
