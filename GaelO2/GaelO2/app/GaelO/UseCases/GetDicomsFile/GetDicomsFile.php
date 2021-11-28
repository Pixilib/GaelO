<?php

namespace App\GaelO\UseCases\GetDicomsFile;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\VisitService;
use Exception;

class GetDicomsFile{

    private AuthorizationVisitService $authorizationService;
    private VisitService $visitService;
    private OrthancService $orthancService;

    public function __construct(AuthorizationVisitService $authorizationService, VisitService $visitService, OrthancService $orthancService)
    {
        $this->orthancService = $orthancService;
        $this->visitService = $visitService;
        $this->authorizationService = $authorizationService;
        $this->orthancService->setOrthancServer(true);
    }

    public function execute(GetDicomsFileRequest $getDicomsRequest, GetDicomsFileResponse $getDicomsResponse){

        try{

            $studyName = $getDicomsRequest->studyName;
            //Authorization Check
            $this->checkAuthorization($getDicomsRequest->currentUserId, $getDicomsRequest->visitId, $getDicomsRequest->role, $studyName);
            //Visits data
            $this->visitService->setVisitId($getDicomsRequest->visitId);
            $visitContext = $this->visitService->getVisitContext();

            $visitType = $visitContext['visit_type']['name'];
            $visitGroup =  $visitContext['visit_type']['visit_group']['modality'];
            $patientId = $visitContext['patient']['id'];

            //Get SeriesOrthancID from database to be downloaded
            //SK ICI PASSER DIRECTEMENT PAR LE REPOSITORY
            $this->orthancSeriesIDs = $this->visitService->getVisitSeriesIdsDicomArray(false);
            //First output the filename, then the controller will call outputStream to get content of orthanc response
            $getDicomsResponse->filename = 'DICOM_'.$studyName.'_'.$visitGroup.'_'.$visitType.'_'.$patientId.'.zip';

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

    private function checkAuthorization(int $currentUserId, int $visitId, string $role, string $studyName){
        $this->authorizationService->setUserId($currentUserId);
        $this->authorizationService->setVisitId($visitId);
        $this->authorizationService->setStudyName($studyName);
        if( !$this->authorizationService->isVisitAllowed($role) ){
            throw new GaelOForbiddenException();
        }
    }

    public function outputStream(){
        $this->orthancService->getOrthancZipStream($this->orthancSeriesIDs);
    }

}
