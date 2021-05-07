<?php

namespace App\GaelO\UseCases\GetInvestigatorFormsFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetInvestigatorFormsFromVisitType {

    private AuthorizationService $authorizationService;
    private VisitRepositoryInterface $visitRepositoryInterface;

    public function __construct(AuthorizationService $authorizationService, VisitRepositoryInterface $visitRepositoryInterface)
    {
        $this->authorizationService = $authorizationService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;

    }

    public function execute(GetInvestigatorFormsFromVisitTypeRequest $getInvestigatorFormsFromVisitTypeRequest, GetInvestigatorFormsFromVisitTypeResponse $getInvestigatorFormsFromVisitTypeResponse){

        try {

            $studyName = $getInvestigatorFormsFromVisitTypeRequest->studyName;
            $this->checkAuthorization($getInvestigatorFormsFromVisitTypeRequest->currentUserId, $studyName);

            //Get Visits in the asked visitTypeId
            $visits = $this->visitRepositoryInterface->getVisitsInVisitType($getInvestigatorFormsFromVisitTypeRequest->visitTypeId, false, null, false);
            //make visitsId array
            $visitsId = array_map(function($visit){ return $visit['id']; }, $visits);

            //Get Validated review for these visits
            $reviews = $this->reviewRepositoryInterface->getReviewFromVisitIdArrayStudyName($visitsId, $studyName, false);

            $answer = [];

            foreach ($reviews as $review) {

                $reviewEntity = ReviewFormEntity::fillFromDBReponseArray($review);
                $answer[] = $reviewEntity;

            }

            $getInvestigatorFormsFromVisitTypeResponse->body = $answer;
            $getInvestigatorFormsFromVisitTypeResponse->status = 200;
            $getInvestigatorFormsFromVisitTypeResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $getInvestigatorFormsFromVisitTypeResponse->body = $e->getErrorBody();
            $getInvestigatorFormsFromVisitTypeResponse->status = $e->statusCode;
            $getInvestigatorFormsFromVisitTypeResponse->statusText = $e->statusText;

        } catch (Exception $e) {
            throw $e;
        }

    }

    private function checkAuthorization(int $userId, string $studyName)
    {
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        if (!$this->authorizationService->isRoleAllowed($studyName)) {
            throw new GaelOForbiddenException();
        };
    }

}
