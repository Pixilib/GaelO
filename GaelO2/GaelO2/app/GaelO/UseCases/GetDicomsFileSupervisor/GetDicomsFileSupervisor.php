<?php

namespace App\GaelO\UseCases\GetDicomsFileSupervisor;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\DicomSeriesRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\OrthancService;
use Exception;

class GetDicomsFileSupervisor {

    private AuthorizationService $authorizationService;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;
    private OrthancService $orthancService;
    private VisitRepositoryInterface $visitRepositoryInterface;

    public function __construct(OrthancService $orthancService,
                                AuthorizationService $authorizationService,
                                DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface,
                                VisitRepositoryInterface $visitRepositoryInterface)
    {
        $this->authorizationService = $authorizationService;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->orthancService = $orthancService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;

    }

    public function execute(GetDicomsFileSupervisorRequest $getDicomsFileSupervisorRequest, GetDicomsFileSupervisorResponse $getDicomsFileSupervisorResponse){

        try{

            if(empty($getDicomsFileSupervisorRequest->seriesInstanceUID) ){
                throw new GaelOBadRequestException('Missing Series Instance UID');
            }

            //Get Related visit ID of requested seriesInstanceUID
            $visitIds = $this->dicomSeriesRepositoryInterface->getRelatedVisitIdFromSeriesInstanceUID($getDicomsFileSupervisorRequest->seriesInstanceUID);

            //Get Contexts of these visits
            $contexts = $this->visitRepositoryInterface->getVisitContextByVisitIdArray($visitIds);

            //Extract parent StudyName
            $studyNames = [];
            foreach($contexts as $context){
                $studyNames[] = $context['visit_type']['visit_group']['study_name'];
            }

            $uniqueStudyName = array_values(array_unique($studyNames));

            //Check that all requested series comes from the same study
            if(sizeof($uniqueStudyName) != 1){
                throw new GaelOBadRequestException('Requested Series should come from the same study');
            }

            //Check that currentUser is Supervisor in this study
            $this->checkAuthorization($getDicomsFileSupervisorRequest->currentUserId, $uniqueStudyName[0]);

            //getOrthancSeriesIdArray
            $this->orthancSeriesIDs = $this->dicomSeriesRepositoryInterface->getSeriesOrthancIDOfSeriesInstanceUID($getDicomsFileSupervisorRequest->seriesInstanceUID);

            //First output the filename, then the controller will call outputStream to get content of orthanc response
            $getDicomsFileSupervisorResponse->filename = 'DICOM_Export_'.$getDicomsFileSupervisorRequest->studyName.'.zip';

            $getDicomsFileSupervisorResponse->status = 200;
            $getDicomsFileSupervisorResponse->statusText = 'OK';

        }catch (GaelOException $e){

            $getDicomsFileSupervisorResponse->status = $e->statusCode;
            $getDicomsFileSupervisorResponse->statusText = $e->statusText;
            $getDicomsFileSupervisorResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $currentUserId, string $studyName){

        $this->authorizationService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        if( ! $this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        }

    }

    public function outputStream(){
        $this->orthancService->getOrthancZipStream($this->orthancSeriesIDs);
    }

}
