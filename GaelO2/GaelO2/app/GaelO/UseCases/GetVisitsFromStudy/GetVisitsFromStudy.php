<?php

namespace App\GaelO\UseCases\GetVisitsFromStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Entities\VisitEntity;
use Exception;

class GetVisitsFromStudy
{

    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(VisitRepositoryInterface $visitRepositoryInterface, AuthorizationService $authorizationService)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetVisitsFromStudyRequest $getVisitsFromStudyRequest, GetVisitsFromStudyResponse $getVisitsFromStudyResponse)
    {

        try {

            $studyName = $getVisitsFromStudyRequest->studyName;

            $this->checkAuthorization($getVisitsFromStudyRequest->currentUserId, $getVisitsFromStudyRequest->studyName);

            $dbData = $this->visitRepositoryInterface->getVisitsInStudy($studyName, true, false);
            $responseArray = [];
            foreach ($dbData as $data) {
                $responseEntity = VisitEntity::fillFromDBReponseArray($data);
                $responseEntity->setPatientEntity($data['patient']);
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
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        if (!$this->authorizationService->isRoleAllowed($studyName)) {
            throw new GaelOForbiddenException();
        }
    }
}