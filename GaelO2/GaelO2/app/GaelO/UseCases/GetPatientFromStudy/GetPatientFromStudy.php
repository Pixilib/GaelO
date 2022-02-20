<?php

namespace App\GaelO\UseCases\GetPatientFromStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyRequest;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyResponse;
use App\GaelO\Entities\PatientEntity;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\StudyService;
use Exception;

class GetPatientFromStudy {

    private PatientRepositoryInterface $patientRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;
    private StudyService $studyService;

    public function __construct(
        PatientRepositoryInterface $patientRepositoryInterface,
        AuthorizationStudyService $authorizationStudyService,
        StudyRepositoryInterface $studyRepositoryInterface,
        StudyService $studyService
        ){
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
        $this->studyService = $studyService;
    }

    public function execute(GetPatientFromStudyRequest $patientRequest, GetPatientFromStudyResponse $patientResponse) : void
    {
        try{

            $studyName = $patientRequest->studyName;

            $this->checkAuthorization($patientRequest->currentUserId, $studyName);

            //Get Patient from Original Study Name if Ancillary Study
            $studyEntity = $this->studyRepositoryInterface->find($studyName);
            $this->studyService->setStudyEntity($studyEntity);
            $originalStudyName = $this->studyService->getOriginalStudyName();

            $patientsDbEntities = $this->patientRepositoryInterface->getPatientsInStudy($originalStudyName);
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
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if ( ! $this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)){
            throw new GaelOForbiddenException();
        };
    }


}

?>
