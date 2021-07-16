<?php

namespace App\GaelO\UseCases\GetPatientsInStudyFromCenters;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCentersRequest;
use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCentersResponse;
use App\GaelO\Entities\PatientEntity;
use Exception;

class GetPatientsInStudyFromCenters {

    private PatientRepositoryInterface $patientRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(PatientRepositoryInterface $patientRepositoryInterface, AuthorizationService $authorizationService, VisitRepositoryInterface $visitRepositoryInterface){
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->authorizationService = $authorizationService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function execute(GetPatientsInStudyFromCentersRequest $getPatientsInStudyFromCentersRequest, GetPatientsInStudyFromCentersResponse $getPatientsInStudyFromCentersResponse) : void
    {
        try{

            $this->checkAuthorization($getPatientsInStudyFromCentersRequest->currentUserId, $getPatientsInStudyFromCentersRequest->studyName);

            $studyName = $getPatientsInStudyFromCentersRequest->studyName;
            $centerCodes = $getPatientsInStudyFromCentersRequest->centerCodes;
            
            $responseArray = [];
            $patientsDbEntities = $this->patientRepositoryInterface->getPatientsInStudyInCenters($studyName, $centerCodes);
            
            foreach($patientsDbEntities as $patientEntity){   
                $patientEntity = PatientEntity::fillFromDBReponseArray($patientEntity);
                $responseArray[] = $patientEntity;
            }

            $getPatientsInStudyFromCentersResponse->body = $responseArray;
            $getPatientsInStudyFromCentersResponse->status = 200;
            $getPatientsInStudyFromCentersResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $getPatientsInStudyFromCentersResponse->body = $e->getErrorBody();
            $getPatientsInStudyFromCentersResponse->status = $e->statusCode;
            $getPatientsInStudyFromCentersResponse->statusText = $e->statusText;

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
