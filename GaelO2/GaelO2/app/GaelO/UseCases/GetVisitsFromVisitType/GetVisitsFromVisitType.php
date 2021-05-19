<?php

namespace App\GaelO\UseCases\GetVisitsFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Entities\VisitEntity;
use Exception;

class GetVisitsFromVisitType
{
    private VisitRepositoryInterface $visitRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(AuthorizationService $authorizationService, VisitRepositoryInterface $visitRepositoryInterface)
    {
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetVisitsFromVisitTypeRequest $getVisitsFromVisitTypeRequest, GetVisitsFromVisitTypeResponse $getVisitsFromVisitTypeResponse)
    {
        try {

            $studyName = $getVisitsFromVisitTypeRequest->studyName;

            //SK ICI IL FAUT CHECKER QUE LE VISITTYPE EST BIEN LIE A UNE STUDY AVEC AUTORISATON POUR LE USER
            $this->checkAuthorization($getVisitsFromVisitTypeRequest->currentUserId, $studyName);

            $dbData = $this->visitRepositoryInterface->getVisitsInVisitType($getVisitsFromVisitTypeRequest->visitTypeId, true, $studyName);

            $responseArray = [];
            foreach ($dbData as $data) {
                $responseEntity = VisitEntity::fillFromDBReponseArray($data);
                $responseEntity->setReviewVisitStatus($data['review_status']['review_status'], $data['review_status']['review_conclusion_value'], $data['review_status']['review_conclusion_date']);
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
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        if (!$this->authorizationService->isRoleAllowed($studyName)) {
            throw new GaelOForbiddenException();
        }
    }
}
