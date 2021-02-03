<?php

namespace App\GaelO\UseCases\GetDicomsFile;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationDicomWebService;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\VisitService;
use Exception;

class GetDicomsFile{

    private AuthorizationDicomWebService $authorizationService;
    private VisitService $visitService;
    private OrthancService $orthancService;

    public function __construct(AuthorizationDicomWebService $authorizationService, VisitService $visitService, OrthancService $orthancService)
    {
        $this->orthancService = $orthancService;
        $this->visitService = $visitService;
        $this->authorizationService = $authorizationService;
        $this->orthancService->setOrthancServer(false);
    }

    public function execute(GetDicomsFileRequest $getDicomsRequest, GetDicomsFileResponse $getDicomsResponse){

        try{

            //Checker Authorization
            $this->checkAuthorization($getDicomsRequest->currentUserId, $getDicomsRequest->visitId, $getDicomsRequest->role);
            //Visits data
            $this->visitService->setVisitId($getDicomsRequest->visitId);
            $visitContext = $this->visitService->getVisitContext();
            $studyName = $visitContext['visit_type']['visit_group']['study_name'];
            $visitType = $visitContext['visit_type']['name'];
            $visitGroup =  $visitContext['visit_type']['visit_group']['modality'];
            $patientCode = $visitContext['patient']['code'];

            //Get SeriesOrthancID from database to be downloaded
            $this->orthancSeriesIDs = $this->visitService->getVisitSeriesIdsDicomArray($getDicomsRequest->visitId, false);
            //First output the filename, then the controller will call outputStream to get content of orthanc response
            $getDicomsResponse->filename = 'DICOM_'.$studyName.'_'.$visitGroup.'_'.$visitType.'_'.$patientCode.'zip';

            $getDicomsResponse->status = 200;
            $getDicomsResponse->statusText = 'OK';

        }catch (GaelOException $e){
            $getDicomsResponse->status = $e->statusCode;
            $getDicomsResponse->statusText = $e->statusText;
            $getDicomsResponse->body = $e->getErrorBody();
        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, int $visitId, string $role){
        $this->authorizationService->setCurrentUserAndRole($currentUserId, $role);
        $this->authorizationService->setVisitId($visitId);
        if( ! $this->authorizationService->isDicomAllowed()){
            throw new GaelOForbiddenException();
        }
    }

    public function outputStream(){
        $this->orthancService->getOrthancZipStream($this->orthancSeriesIDs);
    }

}
