<?php

namespace App\GaelO\UseCases\GetDicoms;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use Exception;

class GetDicoms{

    private AuthorizationVisitService $authorizationVisitService;
    private DicomStudyRepositoryInterface $dicomStudyRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(DicomStudyRepositoryInterface $dicomStudyRepositoryInterface, AuthorizationVisitService $authorizationVisitService, UserRepositoryInterface $userRepositoryInterface){
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
        $this->userRepositoryInterface = $userRepositoryInterface;
    }

    public function execute(GetDicomsRequest $getDicomsRequest, GetDicomsResponse $getDicomResponse){
        try{

            $this->checkAuthorization($getDicomsRequest->visitId, $getDicomsRequest->currentUserId, $getDicomsRequest->role);

            //If Supervisor include deleted studies
            $includeTrashed = $getDicomsRequest->role === Constants::ROLE_SUPERVISOR;
            $data = $this->dicomStudyRepositoryInterface->getDicomsDataFromVisit($getDicomsRequest->visitId, $includeTrashed);

            $user = $this->userRepositoryInterface->find($data[0]['uploader_id']);
            $responseArray = [];

            foreach($data as $study){
                $study['uploader_username'] = $user['username'];
                $studyEntity = DicomStudyEntity::fillFromDBReponseArray($study);
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
