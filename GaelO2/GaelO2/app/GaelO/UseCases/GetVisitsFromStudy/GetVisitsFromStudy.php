<?php

namespace App\GaelO\UseCases\GetVisitsFromStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Entities\VisitEntity;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetVisitsFromStudy
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(
        VisitRepositoryInterface $visitRepositoryInterface,
        StudyRepositoryInterface $studyRepositoryInterface,
        AuthorizationStudyService $authorizationStudyService
    ) {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(GetVisitsFromStudyRequest $getVisitsFromStudyRequest, GetVisitsFromStudyResponse $getVisitsFromStudyResponse)
    {

        try {

            $studyName = $getVisitsFromStudyRequest->studyName;

            $this->checkAuthorization($getVisitsFromStudyRequest->currentUserId, $getVisitsFromStudyRequest->studyName);

            if (is_null($getVisitsFromStudyRequest->visitTypeId)) {
                //Get Original Study name for ancilaries studies
                $studyEntity = $this->studyRepositoryInterface->find($studyName);
                $originalStudyName = $studyEntity->getOriginalStudyName($studyEntity);

                $dbData = $this->visitRepositoryInterface->getVisitsInStudy($originalStudyName, true, false);
            } else {
                $dbData = $this->visitRepositoryInterface->getVisitsInVisitType($getVisitsFromStudyRequest->visitTypeId, true, $studyName, false, true);
            }

            $responseArray = [];
            foreach ($dbData as $data) {
                $responseEntity = VisitEntity::fillFromDBReponseArray($data);
                $responseEntity->setPatientEntity($data['patient']);
                //EO Quick fix, à modifier
                if(isset($data['patient']['center'])) $responseEntity->patient->fillCenterDetails($data['patient']['center']['name'], $data['patient']['center']['country_code']);
                $responseEntity->setVisitContext(
                    $data['visit_type']['visit_group'],
                    $data['visit_type']
                );
                $responseEntity->setReviewVisitStatus($data['review_status']['review_status'], $data['review_status']['review_conclusion_value'], $data['review_status']['review_conclusion_date'], null);
                $responseArray[] = $responseEntity;
            }

            $getVisitsFromStudyResponse->body = $responseArray;
            $getVisitsFromStudyResponse->status = 200;
            $getVisitsFromStudyResponse->statusText = 'OK';
        } catch (GaelOException $e) {

            $getVisitsFromStudyResponse->body = $e->getErrorBody();
            $getVisitsFromStudyResponse->status  = $e->statusCode;
            $getVisitsFromStudyResponse->statusText = $e->statusText;
        } catch (Exception $e) {

            throw $e;
        }
    }

    private function checkAuthorization(int $userId, String $studyName)
    {
        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
