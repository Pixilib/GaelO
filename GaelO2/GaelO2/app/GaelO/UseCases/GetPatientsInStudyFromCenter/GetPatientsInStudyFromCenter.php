<?php

namespace App\GaelO\UseCases\GetPatientsInStudyFromCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetPatientsInStudyFromCenter\GetPatientsInStudyFromCenterRequest;
use App\GaelO\UseCases\GetPatientsInStudyFromCenter\GetPatientsInStudyFromCenterResponse;
use App\GaelO\Entities\PatientEntity;
use Exception;

class GetPatientsInStudyFromCenter {

    private PatientRepositoryInterface $patientRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(PatientRepositoryInterface $patientRepositoryInterface, AuthorizationService $authorizationService){
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetPatientsInStudyFromCenterRequest $patientRequest, GetPatientsInStudyFromCenterResponse $patientResponse) : void
    {
        try{

            $this->checkAuthorization($patientRequest->currentUserId, $patientRequest->studyName);

            $studyName = $patientRequest->studyName;
            $centerCode = $patientRequest->centerCode;

            $patientsDbEntities = $this->patientRepositoryInterface->getPatientsInStudyInCenters($studyName, $centerCode);
            
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
