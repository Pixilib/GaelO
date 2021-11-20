<?php

namespace App\GaelO\UseCases\GetInvestigatorFormsFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Entities\ReviewEntity;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetInvestigatorFormsFromVisitType {

    private AuthorizationStudyService $authorizationStudyService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(
        AuthorizationStudyService $authorizationStudyService,
        VisitRepositoryInterface $visitRepositoryInterface,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        )
    {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

    public function execute(GetInvestigatorFormsFromVisitTypeRequest $getInvestigatorFormsFromVisitTypeRequest, GetInvestigatorFormsFromVisitTypeResponse $getInvestigatorFormsFromVisitTypeResponse){

        try {

            $studyName = $getInvestigatorFormsFromVisitTypeRequest->studyName;
            //SK ICI VERIFIER QUE VISIT TYPE EST BIEN DANS UNE STUDY PERMISE
            $this->checkAuthorization($getInvestigatorFormsFromVisitTypeRequest->currentUserId, $studyName);

            //Get Visits in the asked visitTypeId
            $visits = $this->visitRepositoryInterface->getVisitsInVisitType($getInvestigatorFormsFromVisitTypeRequest->visitTypeId, false, null, false);
            //make visitsId array
            $visitsId = array_map(function($visit){ return $visit['id']; }, $visits);

            //Get Validated review for these visits
            $reviews = $this->reviewRepositoryInterface->getInvestigatorsFormsFromVisitIdArrayStudyName($visitsId, $studyName, false, true);

            $answer = [];

            foreach ($reviews as $review) {
                $reviewEntity = ReviewEntity::fillFromDBReponseArray($review);
                $reviewEntity->setUserDetails($review['user']['lastname'], $review['user']['firstname'], $review['user']['center_code']);
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

        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy( Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        };
    }

}
