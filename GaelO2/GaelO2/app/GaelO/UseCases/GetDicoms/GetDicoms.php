<?php

namespace App\GaelO\UseCases\GetDicoms;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\DicomSeriesEntity;
use App\GaelO\Entities\DicomStudyEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use Exception;

class GetDicoms{

    private AuthorizationVisitService $authorizationVisitService;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;

    public function __construct(DicomStudyRepositoryInterface $dicomStudyRepositoryInterface, AuthorizationVisitService $authorizationVisitService){
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
    }

    public function execute(GetDicomsRequest $getDicomsRequest, GetDicomsResponse $getDicomResponse){
        try{

            $this->checkAuthorization($getDicomsRequest->visitId, $getDicomsRequest->currentUserId, $getDicomsRequest->role);

            //If Supervisor include deleted studies
            $includeTrashed = $getDicomsRequest->role === Constants::ROLE_SUPERVISOR || $getDicomsRequest->role === Constants::ROLE_INVESTIGATOR;

            $data = [];

            if($includeTrashed){
                $data = $this->dicomStudyRepositoryInterface->getDicomsDataFromVisit($getDicomsRequest->visitId, $includeTrashed);
            }else{
                $data[] = $this->dicomStudyRepositoryInterface->getDicomsDataFromVisit($getDicomsRequest->visitId, $includeTrashed);
            }

            $responseArray = [];

            foreach($data as $study){
                $studyEntity = DicomStudyEntity::fillFromDBReponseArray($study);
                $studyEntity->addUploaderDetails($study['uploader']);
                foreach($study['dicom_series'] as $series){
                    $seriesEntity = DicomSeriesEntity::fillFromDBReponseArray($series);
                    $studyEntity->series[] = $seriesEntity;
                }

                $responseArray[] = $studyEntity;
            }

            $getDicomResponse->status = 200;
            $getDicomResponse->statusText = 'OK';
            $getDicomResponse->body = $responseArray;

        }catch (GaelOException $e){

            $getDicomResponse->status = $e->statusCode;
            $getDicomResponse->statusText = $e->statusText;
            $getDicomResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $visitId, int $userId, string $role) : void {
        $this->authorizationVisitService->setCurrentUserAndRole($userId, $role);
        $this->authorizationVisitService->setVisitId($visitId);
        if ( ! $this->authorizationVisitService->isVisitAllowed() ){
            throw new GaelOForbiddenException();
        }

    }

}
