<?php

namespace App\GaelO\UseCases\GetPatientVisit;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationPatientService;
use App\GaelO\UseCases\GetVisit\VisitEntity;
use Exception;

class GetPatientVisit {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationPatientService $authorizationPatientService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationPatientService = $authorizationPatientService;
    }

    public function execute(GetPatientVisitRequest $getPatientVisitRequest, GetPatientVisitResponse $getPatientVisitResponse){

        try{
            $this->checkAuthorization($getPatientVisitRequest->currentUserId, $getPatientVisitRequest->patientCode, $getPatientVisitRequest->role);
            $visitsArray = $this->persistenceInterface->getPatientsVisitsWithReviewStatus($getPatientVisitRequest->patientCode, $getPatientVisitRequest->studyName);

            $responseArray = [];
            foreach($visitsArray as $data){

                $visitTypeName = $data['visit_type']['name'];
                $visitTypeOrder = $data['visit_type']['order'];
                $visitTypeOptional = $data['visit_type']['optional'];
                $visitGroupModality =  $data['visit_type']['visit_group']['modality'];
                $visitGroupId =  $data['visit_type']['visit_group']['id'];

                $reviewStatus =  $data['review_status'];
                $reviewConclusionValue = $getPatientVisitRequest->role === Constants::ROLE_SUPERVISOR ? $data['review_conclusion_value'] : null;
                $reviewConclusionDate =  $getPatientVisitRequest->role === Constants::ROLE_SUPERVISOR ? $data['review_conclusion_date'] : null;

                $visitEntity = VisitEntity::fillFromDBReponseArray($data);
                $visitEntity->setVisitContext($visitGroupModality, $visitTypeName, $visitTypeOrder, $visitTypeOptional, $visitGroupId);
                $visitEntity->setReviewVisitStatus($reviewStatus, $reviewConclusionValue, $reviewConclusionDate);
                $responseArray[] = $visitEntity;
            }

            $getPatientVisitResponse->body = $responseArray;
            $getPatientVisitResponse->status = 200;
            $getPatientVisitResponse->statusText = 'OK';

        } catch(GaelOException $e){

            $getPatientVisitResponse->status = $e->statusCode;
            $getPatientVisitResponse->statusText = $e->statusText;
            $getPatientVisitResponse->body = $e->getErrorBody();

        } catch(Exception $e){

            throw $e;

        }

    }

    private function checkAuthorization(int $userId, int $patientCode, string $role){
        $this->authorizationPatientService->setCurrentUserAndRole($userId, $role);
        $this->authorizationPatientService->setPatient($patientCode);
        if( ! $this->authorizationPatientService->isPatientAllowed()){
            throw new GaelOForbiddenException();
        }
    }
}
