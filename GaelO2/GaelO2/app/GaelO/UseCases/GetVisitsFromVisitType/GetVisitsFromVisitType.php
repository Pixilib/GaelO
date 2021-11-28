<?php

namespace App\GaelO\UseCases\GetVisitsFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Entities\VisitEntity;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetVisitsFromVisitType
{
    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(AuthorizationStudyService $authorizationStudyService, VisitRepositoryInterface $visitRepositoryInterface)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(GetVisitsFromVisitTypeRequest $getVisitsFromVisitTypeRequest, GetVisitsFromVisitTypeResponse $getVisitsFromVisitTypeResponse)
    {
        try {

            $studyName = $getVisitsFromVisitTypeRequest->studyName;

            //SK ICI IL FAUT CHECKER QUE LE VISITTYPE EST BIEN LIE A UNE STUDY AVEC AUTORISATON POUR LE USER
            $this->checkAuthorization($getVisitsFromVisitTypeRequest->currentUserId, $studyName);

            $dbData = $this->visitRepositoryInterface->getVisitsInVisitType($getVisitsFromVisitTypeRequest->visitTypeId, true, $studyName, false, true );

            $responseArray = [];
            foreach ($dbData as $data) {
                $responseEntity = VisitEntity::fillFromDBReponseArray($data);
                $responseEntity->setReviewVisitStatus($data['review_status']['review_status'], $data['review_status']['review_conclusion_value'], $data['review_status']['review_conclusion_date'], null);
                $responseEntity->setPatientEntity($data['patient']);
                $responseEntity->patient->fillCenterDetails($data['patient']['center']['name'], $data['patient']['center']['country_code']);
                $responseArray[] = $responseEntity;
            }

            $getVisitsFromVisitTypeResponse->body = $responseArray;
            $getVisitsFromVisitTypeResponse->status = 200;
            $getVisitsFromVisitTypeResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $getVisitsFromVisitTypeResponse->body = $e->getErrorBody();
            $getVisitsFromVisitTypeResponse->status  = $e->statusCode;
            $getVisitsFromVisitTypeResponse->statusText = $e->statusText;

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
