<?php

namespace App\GaelO\UseCases\DeleteSeries;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\DicomSeriesService;
use Exception;

class DeleteSeries{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private DicomSeriesService $dicomSeriesService;
    private AuthorizationVisitService $authorizationVisitService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;


    public function __construct( VisitRepositoryInterface $visitRepositoryInterface, DicomSeriesService $dicomSeriesService, AuthorizationVisitService $authorizationVisitService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->dicomSeriesService = $dicomSeriesService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function execute(DeleteSeriesRequest $deleteSeriesRequest, DeleteSeriesResponse $deleteSeriesResponse){

        try{

            if(empty($deleteSeriesRequest->reason)){
                throw new GaelOBadRequestException("A reason must be specified");
            }

            $seriesData = $this->dicomSeriesService->getDicomSeries($deleteSeriesRequest->seriesInstanceUID, false);
            $visitId = $seriesData['dicom_study']['visit_id'];

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);

            $this->checkAuthorization($deleteSeriesRequest->currentUserId, $visitId, $deleteSeriesRequest->role, $visitContext['state_quality_control']);

            $this->dicomSeriesService->deleteSeries($deleteSeriesRequest->seriesInstanceUID, $deleteSeriesRequest->role);


            $studyName = $visitContext['visit_type']['visit_group']['study_name'];

            $actionDetails = [
                'seriesInstanceUID'=>$seriesData['series_uid'],
                'reason'=>$deleteSeriesRequest->reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $deleteSeriesRequest->currentUserId,
                $deleteSeriesRequest->role,
                $studyName,
                $visitId,
                Constants::TRACKER_DELETE_DICOM_SERIES,
                $actionDetails
            );

            $deleteSeriesResponse->status = 200;
            $deleteSeriesResponse->statusText =  'OK';

        } catch (GaelOException $e){

            $deleteSeriesResponse->body = $e->getErrorBody();
            $deleteSeriesResponse->status = $e->statusCode;
            $deleteSeriesResponse->statusText =  $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    public function checkAuthorization(int $userId, int $visitId, string $role, string $qcStatus) : void{

        //Series delete only for Investigator, Controller, Supervisor
        if( !in_array($role, [Constants::ROLE_INVESTIGATOR, Constants::ROLE_CONTROLLER, Constants::ROLE_SUPERVISOR]) ){
            throw new GaelOForbiddenException();
        }

        //If QC is done, can't remove series
        if( in_array($qcStatus, [Constants::QUALITY_CONTROL_ACCEPTED, Constants::QUALITY_CONTROL_REFUSED])){
            throw new GaelOForbiddenException();
        }

        $this->authorizationVisitService->setCurrentUserAndRole($userId, $role);
        $this->authorizationVisitService->setVisitId($visitId);
        ;
        if ( ! $this->authorizationVisitService->isVisitAllowed()){
            throw new GaelOForbiddenException();
        }

    }
}
