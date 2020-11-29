<?php

namespace App\GaelO\UseCases\GetPatientFromStudy;

use App\GaelO\Constants\Constants;
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

            $this->checkAuthorization($patientRequest->currentUserId, $patientRequest->studyName);

            $studyName = $patientRequest->studyName;
            $patientsDbEntities = $this->persistenceInterface->getPatientsInStudy($studyName);
            $responseArray = [];
            foreach($patientsDbEntities as $patientEntity){
                $responseArray[] = PatientEntity::fillFromDBReponseArray($patientEntity);
            }

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

    private function checkAuthorization(int $currentUserId, string $studyName){
        $this->authorizationService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        if ( ! $this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        };
    }


}

?>
