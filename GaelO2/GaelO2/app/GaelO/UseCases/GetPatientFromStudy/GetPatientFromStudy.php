<?php

namespace App\GaelO\UseCases\GetPatientFromStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyRequest;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyResponse;
use App\GaelO\Entities\PatientEntity;
use Exception;

class GetPatientFromStudy {

    private PatientRepositoryInterface $patientRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(PatientRepositoryInterface $patientRepositoryInterface, AuthorizationService $authorizationService){
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetPatientFromStudyRequest $patientRequest, GetPatientFromStudyResponse $patientResponse) : void
    {
        try{

            $this->checkAuthorization($patientRequest->currentUserId, $patientRequest->studyName);

            $studyName = $patientRequest->studyName;
            $patientsDbEntities = $this->patientRepositoryInterface->getPatientsInStudy($studyName);
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