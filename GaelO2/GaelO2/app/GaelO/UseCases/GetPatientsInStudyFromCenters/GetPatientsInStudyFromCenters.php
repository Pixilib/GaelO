<?php

namespace App\GaelO\UseCases\GetPatientsInStudyFromCenters;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCentersRequest;
use App\GaelO\UseCases\GetPatientsInStudyFromCenters\GetPatientsInStudyFromCentersResponse;
use App\GaelO\Entities\PatientEntity;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\StudyService;
use Exception;

class GetPatientsInStudyFromCenters {

    private PatientRepositoryInterface $patientRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;
    private StudyService $studyService;

    public function __construct(
        PatientRepositoryInterface $patientRepositoryInterface,
        StudyRepositoryInterface $studyRepositoryInterface,
        AuthorizationStudyService $authorizationStudyService,
        StudyService $studyService
        ){
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
        $this->studyService = $studyService;
    }

    public function execute(GetPatientsInStudyFromCentersRequest $getPatientsInStudyFromCentersRequest, GetPatientsInStudyFromCentersResponse $getPatientsInStudyFromCentersResponse) : void
    {
        try{

            $studyName = $getPatientsInStudyFromCentersRequest->studyName;
            $centerCodes = $getPatientsInStudyFromCentersRequest->centerCodes;

            $this->checkAuthorization($getPatientsInStudyFromCentersRequest->currentUserId, $studyName);

            //Get Patient from Original Study Name if Ancillary Study
            $studyEntity = $this->studyRepositoryInterface->find($studyName);
            $this->studyService->setStudyEntity($studyEntity);
            $originalStudyName = $this->studyService->getOriginalStudyName();

            $responseArray = [];
            $patientsDbEntities = $this->patientRepositoryInterface->getPatientsInStudyInCenters($originalStudyName, $centerCodes);

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
        $this->authorizationStudyService->setStudyName($studyName);
        $this->authorizationStudyService->setUserId($currentUserId);
        if ( ! $this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)){
            throw new GaelOForbiddenException();
        };
    }


}
