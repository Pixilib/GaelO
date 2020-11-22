<?php

namespace App\GaelO\UseCases\GetPatientFromStudy;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyRequest;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyResponse;
use App\GaelO\UseCases\GetPatient\PatientEntity;
use Exception;

class GetPatientFromStudy {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetPatientFromStudyRequest $patientRequest, GetPatientFromStudyResponse $patientResponse) : void
    {
        try{

            $this->checkAuthorization($patientRequest->currentUserId, $patientRequest->role, $patientRequest->studyName);

            $studyName = $patientRequest->studyName;
            $dbData = $this->persistenceInterface->getPatientsInStudy($studyName);
            $responseArray = [];
            foreach($dbData as $data){
                $data = $this->persistenceInterface->find($data);
                $responseArray[] = PatientEntity::fillFromDBReponseArray($data);
            }
            //SK ICI SI PAS ADMINISTRATOR CACHER LE CENTER DU PATIENT ?
            $patientResponse->body = $responseArray;
            $patientResponse->status = 200;
            $patientResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $patientResponse->body = $e->getErrorBody();
            $patientResponse->status = $e->statusCode;
            $patientResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $currentUserId, string $role, string $studyName){
        $this->authorizationService->setCurrentUserAndRole($currentUserId, $role);
        if ( ! $this->authorizationService->isRoleAllowed($studyName)){
                    throw new GaelOForbiddenException();
        };
    }


}

?>
