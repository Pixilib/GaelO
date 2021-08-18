<?php

namespace App\GaelO\UseCases\GetPatientsVisitsInStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\UseCases\GetPatientsVisitsInStudy\GetPatientsVisitsInStudyRequest;
use App\GaelO\UseCases\GetPatientsVisitsInStudy\GetPatientsVisitsInStudyResponse;
use App\GaelO\Entities\PatientEntity;
use App\GaelO\Entities\VisitEntity;
use Exception;

class GetPatientsVisitsInStudy {

    private PatientRepositoryInterface $patientRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(PatientRepositoryInterface $patientRepositoryInterface,
        AuthorizationService $authorizationService,
        VisitRepositoryInterface $visitRepositoryInterface)
    {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->authorizationService = $authorizationService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function execute(GetPatientsVisitsInStudyRequest $getPatientsVisitsInStudyRequest, GetPatientsVisitsInStudyResponse $getPatientsVisitsInStudyResponse) : void
    {
        try{

            $this->checkAuthorization($getPatientsVisitsInStudyRequest->currentUserId, $getPatientsVisitsInStudyRequest->studyName);

            $studyName = $getPatientsVisitsInStudyRequest->studyName;
            $patientCodes = $getPatientsVisitsInStudyRequest->patientCodes;

            $responseArray = [];
            $visitsArray = $this->visitRepositoryInterface->getPatientListVisitWithContextAndReviewStatus($patientCodes, $studyName);
            $patientEntities = $this->patientRepositoryInterface->find($patientCodes);

            foreach($patientEntities as $patientEntity) {
                $patientVisits = [];
                $patientVisitsArray = array_filter($visitsArray, function($visit) use ($patientEntity){
                    return $visit['patient_code'] === $patientEntity['code'];
                });

                foreach($patientVisitsArray as $data){
                    $visitEntity = VisitEntity::fillFromDBReponseArray($data);
                    $visitEntity->setVisitContext($data['visit_type']['visit_group'], $data['visit_type'] );
                    $patientVisits[] = $visitEntity;
                }

                $patientEntity = PatientEntity::fillFromDBReponseArray($patientEntity);
                $patientEntity->setVisitsDetails($patientVisits);
                $responseArray[] = $patientEntity;
            }
            $getPatientsVisitsInStudyResponse->body = $responseArray;
            $getPatientsVisitsInStudyResponse->status = 200;
            $getPatientsVisitsInStudyResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $getPatientsVisitsInStudyResponse->body = $e->getErrorBody();
            $getPatientsVisitsInStudyResponse->status = $e->statusCode;
            $getPatientsVisitsInStudyResponse->statusText = $e->statusText;

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
