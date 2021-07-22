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
use App\GaelO\Entities\DicomStudyEntity;
use Exception;

class GetPatientsVisitsInStudy {

    private PatientRepositoryInterface $patientRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(PatientRepositoryInterface $patientRepositoryInterface, 
        AuthorizationService $authorizationService, 
        VisitRepositoryInterface $visitRepositoryInterface,
        DicomStudyRepositoryInterface $dicomStudyRepositoryInterface)
    {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->authorizationService = $authorizationService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->dicomStudyRepositoryInterface = $dicomStudyRepositoryInterface;
    }

    public function execute(GetPatientsVisitsInStudyRequest $getPatientsVisitsInStudyRequest, GetPatientsVisitsInStudyResponse $getPatientsVisitsInStudyResponse) : void
    {
        try{

            $this->checkAuthorization($getPatientsVisitsInStudyRequest->currentUserId, $getPatientsVisitsInStudyRequest->studyName);

            $studyName = $getPatientsVisitsInStudyRequest->studyName;
            $patientCodes = $getPatientsVisitsInStudyRequest->patientCodes;
            
            $responseArray = [];
            $patientEntities = $this->patientRepositoryInterface->find($patientCodes);
            
            foreach($patientEntities as $patientEntity) {
                $patientVisits = [];
                $visitsArray = $this->visitRepositoryInterface->getAllPatientsVisitsWithReviewStatus($patientEntity['code'], $studyName, false);
                
                foreach($visitsArray as $data){    

                    $visitTypeName = $data['visit_type']['name'];
                    $visitTypeOrder = $data['visit_type']['order'];
                    $visitTypeOptional = $data['visit_type']['optional'];
                    $visitTypeLimitLowDays = $data['visit_type']['limit_low_days'];
                    $visitTypeLimitUpDays = $data['visit_type']['limit_up_days'];
                    $visitGroupModality =  $data['visit_type']['visit_group']['modality'];
                    $visitGroupId =  $data['visit_type']['visit_group']['id'];
                    $visitEntity = VisitEntity::fillFromDBReponseArray($data);
                    if($data['upload_status'] === Constants::UPLOAD_STATUS_DONE) {
                        $dicomStudy = $this->dicomStudyRepositoryInterface->getDicomsDataFromVisit($data['id'], false);
                        $visitEntity->setAcquisitionDate($dicomStudy['acquisition_date']);
                    }
                    $visitEntity->setVisitContext($visitGroupModality, $visitTypeName, $visitTypeOrder, $visitTypeOptional, $visitGroupId, $visitTypeLimitLowDays, $visitTypeLimitUpDays);
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

?>
