<?php

namespace App\GaelO\UseCases\GetInvestigatorFormsFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Entities\ReviewEntity;
use App\GaelO\Entities\UserEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Repositories\VisitTypeRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetInvestigatorFormsFromVisitType
{

    private AuthorizationStudyService $authorizationStudyService;
    private VisitTypeRepository $visitTypeRepository;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(
        AuthorizationStudyService $authorizationStudyService,
        VisitTypeRepository $visitTypeRepository,
        VisitRepositoryInterface $visitRepositoryInterface,
        ReviewRepositoryInterface $reviewRepositoryInterface,
    ) {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->visitTypeRepository = $visitTypeRepository;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

    public function execute(GetInvestigatorFormsFromVisitTypeRequest $getInvestigatorFormsFromVisitTypeRequest, GetInvestigatorFormsFromVisitTypeResponse $getInvestigatorFormsFromVisitTypeResponse)
    {

        try {

            $studyName = $getInvestigatorFormsFromVisitTypeRequest->studyName;
            $visitTypeId = $getInvestigatorFormsFromVisitTypeRequest->visitTypeId;

            $visitTypeEntity = $this->visitTypeRepository->find($visitTypeId, true);
            $originalStudyName = $visitTypeEntity['visit_group']['study_name'];

            //Check that the requested study name is an original or ancillary study of this visit type
            if (!AuthorizationStudyService::isOriginalOrAncillaryStudyOf($studyName, $originalStudyName)) {
                throw new GaelOForbiddenException('Forbidden acces to this Visit Type');
            }
            $this->checkAuthorization($getInvestigatorFormsFromVisitTypeRequest->currentUserId, $studyName);

            //Get Visits in the asked visitTypeId
            $visits = $this->visitRepositoryInterface->getVisitsInVisitType($visitTypeId, false, null, false);
            //make visitsId array
            $visitsId = array_map(function ($visit) {
                return $visit['id'];
            }, $visits);

            //Get Validated review for these visits
            $reviews = $this->reviewRepositoryInterface->getInvestigatorsFormsFromVisitIdArrayStudyName($visitsId, $studyName, false, true);

            $answer = [];

            foreach ($reviews as $review) {
                $reviewEntity = ReviewEntity::fillFromDBReponseArray($review);
                $reviewEntity->setUserDetails( UserEntity::fillMinimalFromDBReponseArray($review['user']) );
                $answer[] = $reviewEntity;
            }

            $getInvestigatorFormsFromVisitTypeResponse->body = $answer;
            $getInvestigatorFormsFromVisitTypeResponse->status = 200;
            $getInvestigatorFormsFromVisitTypeResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
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
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
