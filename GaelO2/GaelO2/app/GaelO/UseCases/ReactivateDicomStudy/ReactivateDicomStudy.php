<?php

namespace App\GaelO\UseCases\ReactivateDicomStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\DicomSeriesService;
use Exception;

class ReactivateDicomStudy{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;
    private DicomSeriesService $dicomSeriesService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, AuthorizationVisitService $authorizationVisitService, DicomSeriesService $dicomSeriesService, TrackerRepositoryInterface $trackerRepositoryInterface){
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->dicomSeriesService = $dicomSeriesService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ReactivateDicomStudyRequest $reactivateDicomStudyRequest, ReactivateDicomStudyResponse $reactivateDicomStudyResponse){

        try{

            $studyData = $this->dicomSeriesService->getDicomStudy($reactivateDicomStudyRequest->studyInstanceUID, true);
            $visitId = $studyData['visit_id'];

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);
            $studyName = $visitContext['visit_type']['visit_group']['study_name'];

            $this->checkAuthorization($reactivateDicomStudyRequest->currentUserId, $visitId, $visitContext['state_quality_control']);

            //Change dicom study Activation
            $this->dicomSeriesService->reactivateDicomStudy($reactivateDicomStudyRequest->studyInstanceUID);

            //Tracker
            $actionDetails = [
                'seriesInstanceUID'=>$studyData['study_uid']
            ];

            $this->trackerRepositoryInterface->writeAction(
                $reactivateDicomStudyRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $visitId,
                Constants::TRACKER_REACTIVATE_DICOM_STUDY,
                $actionDetails
            );


            $reactivateDicomStudyResponse->status = 200;
            $reactivateDicomStudyResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $reactivateDicomStudyResponse->status = $e->statusCode;
            $reactivateDicomStudyResponse->statusText = $e->statusText;
            $reactivateDicomStudyResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $userId, int $visitId, string $qcStatus) : void{

        //If QC is done, can't reactivate Study
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
