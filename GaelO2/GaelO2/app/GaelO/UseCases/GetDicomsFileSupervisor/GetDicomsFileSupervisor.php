<?php

namespace App\GaelO\UseCases\GetDicomsFileSupervisor;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\DicomSeriesRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\OrthancService;
use Exception;

class GetDicomsFileSupervisor {

    private AuthorizationService $authorizationService;
    private DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface;
    private OrthancService $orthancService;

    public function __construct( OrthancService $orthancService, AuthorizationService $authorizationService, DicomSeriesRepositoryInterface $dicomSeriesRepositoryInterface)
    {
        $this->authorizationService = $authorizationService;
        $this->dicomSeriesRepositoryInterface = $dicomSeriesRepositoryInterface;
        $this->orthancService = $orthancService;

    }

    public function execute(GetDicomsFileSupervisorRequest $getDicomsFileSupervisorRequest, GetDicomsFileSupervisorResponse $getDicomsFileSupervisorResponse){

        try{

            //Checker Authorization
            //$this->checkAuthorization($getDicomsFileSupervisorRequest->currentUserId, $getDicomsFileSupervisorRequest->studyName);

            //Get Related visit ID of requested seriesInstanceUID
            $visitIds = $this->dicomSeriesRepositoryInterface->getRelatedVisitIdFromSeriesInstanceUID($getDicomsFileSupervisorRequest->seriesInstanceUID);

            //Check that this visitId are allowed for the currentUser
            //SK Essyaer de lister tout les visitContext
            //Verfiier que appartiennent à la meme etude
            // Verifier que cette etude est propriete de l'utilsateur supervisor


            //getOrthancSeriesIdArray
            $this->orthancSeriesIDs = $this->dicomSeriesRepositoryInterface->getSeriesOrthancIDOfSeriesInstanceUID($getDicomsFileSupervisorRequest->seriesInstanceUID);

            //First output the filename, then the controller will call outputStream to get content of orthanc response
            $getDicomsFileSupervisorResponse->filename = 'DICOM_'.$getDicomsFileSupervisorRequest->studyName.'.zip';

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
