<?php

namespace App\GaelO\UseCases\GetPatientsVisitsInStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\UseCases\GetPatientsVisitsInStudy\GetPatientsVisitsInStudyRequest;
use App\GaelO\UseCases\GetPatientsVisitsInStudy\GetPatientsVisitsInStudyResponse;
use App\GaelO\Entities\PatientEntity;
use App\GaelO\Entities\VisitEntity;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetPatientsVisitsInStudy
{

    private PatientRepositoryInterface $patientRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(
        PatientRepositoryInterface $patientRepositoryInterface,
        AuthorizationStudyService $authorizationStudyService,
        VisitRepositoryInterface $visitRepositoryInterface
    ) {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
    }

    public function execute(GetPatientsVisitsInStudyRequest $getPatientsVisitsInStudyRequest, GetPatientsVisitsInStudyResponse $getPatientsVisitsInStudyResponse): void
    {
        try {
            $studyName = $getPatientsVisitsInStudyRequest->studyName;
            $patientIds = $getPatientsVisitsInStudyRequest->patientIds;

            $this->checkAuthorization($getPatientsVisitsInStudyRequest->currentUserId, $studyName);

            $responseArray = [];
            $visitsArray = $this->visitRepositoryInterface->getVisitFromPatientIdsWithContextAndReviewStatus($patientIds, $studyName);
            $patientEntities = $this->patientRepositoryInterface->find($patientIds);

            foreach ($patientEntities as $patientEntity) {
                $patientVisits = [];
                $patientVisitsArray = array_filter($visitsArray, function ($visit) use ($patientEntity) {
                    return $visit['patient_id'] === $patientEntity['id'];
                });

                foreach ($patientVisitsArray as $data) {
                    $visitEntity = VisitEntity::fillFromDBReponseArray($data);
                    $visitEntity->setVisitContext($data['visit_type']['visit_group'], $data['visit_type']);
                    $patientVisits[] = $visitEntity;
                }

                $patientEntity = PatientEntity::fillFromDBReponseArray($patientEntity);
                $patientEntity->setVisitsDetails($patientVisits);
                $responseArray[] = $patientEntity;
            }
            $getPatientsVisitsInStudyResponse->body = $responseArray;
            $getPatientsVisitsInStudyResponse->status = 200;
            $getPatientsVisitsInStudyResponse->statusText = 'OK';
        } catch (GaelOException $e) {

            $getPatientsVisitsInStudyResponse->body = $e->getErrorBody();
            $getPatientsVisitsInStudyResponse->status = $e->statusCode;
            $getPatientsVisitsInStudyResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $studyName)
    {
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        };
    }
}
