<?php

namespace App\GaelO\UseCases\GetCreatableVisits;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationPatientService;
use Exception;

class GetCreatableVisits{

    private AuthorizationPatientService $authorizationPatientService;
    private PatientRepositoryInterface $patientRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;


    public function __construct(AuthorizationPatientService $authorizationPatientService, PatientRepositoryInterface $patientRepositoryInterface, VisitRepositoryInterface $visitRepositoryInterface, StudyRepositoryInterface $studyRepositoryInterface)
    {
        $this->authorizationPatientService = $authorizationPatientService;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
    }

    public function execute(GetCreatableVisitsRequest $getCreatableVisitsRequest, GetCreatableVisitsResponse $getCreatableVisitsResponse){

        try{
            $patientId = $getCreatableVisitsRequest->patientId;
            $patientEntity = $this->patientRepositoryInterface->find($patientId);

            $this->checkAuthorization($getCreatableVisitsRequest->currentUserId, $getCreatableVisitsRequest->patientId, $patientEntity['study_name']);

            //If Patient status different from Included, No further visit creation is possible
            if ($patientEntity['inclusion_status'] !== Constants::PATIENT_INCLUSION_STATUS_INCLUDED) {
                $visitToCreate = [];
            } else {
                $visitToCreate = $this->getAvailableVisitToCreate($patientEntity);
            }

            $getCreatableVisitsResponse->status = 200;
            $getCreatableVisitsResponse->statusText = 'OK';
            $getCreatableVisitsResponse->body = $visitToCreate;

        } catch (GaelOException $e){

            $getCreatableVisitsResponse->status = $e->statusCode;
            $getCreatableVisitsResponse->statusText = $e->statusText;
            $getCreatableVisitsResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $patientId, string $studyName){
        $this->authorizationPatientService->setUserId($userId);
        $this->authorizationPatientService->setStudyName($studyName);
        $this->authorizationPatientService->setPatientId($patientId);
        if ( ! $this->authorizationPatientService->isPatientAllowed(Constants::ROLE_INVESTIGATOR) ){
            throw new GaelOForbiddenException();
        }

    }

    private function getAvailableVisitToCreate(array $patientEntity): array
    {
        //Get Created Patients Visits
        $createdVisitsArray = $this->visitRepositoryInterface->getPatientsVisits($patientEntity['id']);

        $createdVisitMap = [];

        //Build array of Created visit Order indexed by visit group name
        foreach ($createdVisitsArray as $createdVisit) {
            $visitOrder = $createdVisit['visit_type']['order'];
            $visitGroupName = $createdVisit['visit_type']['visit_group']['name'];
            $createdVisitMap[$visitGroupName][] = $visitOrder;
        }


        //Get possible visits groups and types from study
        $studyVisitsDetails = $this->studyRepositoryInterface->getStudyDetails($patientEntity['study_name']);
        $studyVisitMap = [];
        //Reindex possible visits by visit group name and order
        foreach ($studyVisitsDetails['visit_group_details'] as $visitGroupDetails) {

            foreach ($visitGroupDetails['visit_types'] as $visitType) {

                $studyVisitMap[$visitGroupDetails['name']][$visitType['order']] = [
                    'groupId' => $visitType['visit_group_id'],
                    'groupModality' => $visitGroupDetails['modality'],
                    'groupName' => $visitGroupDetails['name'],
                    'typeId' => $visitType['id'],
                    'name' => $visitType['name'],
                    'optional' => $visitType['optional']
                ];
            }
        }
        $visitToCreateMap = [];

        //Search for visits that have not been created
        foreach ($studyVisitMap as $visitGroupName => $visitsArray) {

            foreach ($visitsArray as $visitOrder => $visit) {
                if (!isset($createdVisitMap[$visitGroupName]) || !in_array($visitOrder, $createdVisitMap[$visitGroupName])) {
                    $visit['order'] = $visitOrder;
                    $visitToCreateMap[] = $visit;
                }
            }
        }

        return $visitToCreateMap;
    }

}
