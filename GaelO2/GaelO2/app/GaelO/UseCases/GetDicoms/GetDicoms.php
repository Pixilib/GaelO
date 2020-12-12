<?php

namespace App\GaelO\UseCases\GetDicoms;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationVisitService;
use Exception;

class GetDicoms{

    private AuthorizationVisitService $authorizationVisitService;

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationVisitService $authorizationVisitService){
        $this->authorizationVisitService = $authorizationVisitService;
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetDicomsRequest $getDicomsRequest, GetDicomsResponse $getDicomResponse){
        try{

            $this->checkAuthorization($getDicomsRequest->visitId, $getDicomsRequest->currentUserId, $getDicomsRequest->role);

            //If Supervisor include deleted studies
            $includeTrashed = $getDicomsRequest->role === Constants::ROLE_SUPERVISOR;
            $data = $this->persistenceInterface->getDicomsDataFromVisit($getDicomsRequest->visitId, $includeTrashed);

            $responseArray = [];

            foreach($data as $study){
                $studyEntity = OrthancStudyEntity::fillFromDBReponseArray($study);
                foreach($study['series'] as $series){
                    $seriesEntity = OrthancSeriesEntity::fillFromDBReponseArray($series);
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
