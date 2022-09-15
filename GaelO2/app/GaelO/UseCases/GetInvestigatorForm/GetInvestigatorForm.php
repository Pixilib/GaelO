<?php

namespace App\GaelO\UseCases\GetInvestigatorForm;

use App\GaelO\Entities\ReviewEntity;
use App\GaelO\Entities\UserEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use Exception;

class GetInvestigatorForm
{

    private AuthorizationVisitService $authorizationVisitService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(ReviewRepositoryInterface $reviewRepositoryInterface, AuthorizationVisitService $authorizationVisitService)
    {
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
    }

    public function execute(GetInvestigatorFormRequest $getInvestigatorFormRequest, GetInvestigatorFormResponse $getInvestigatorFormResponse)
    {

        try {

            $this->checkAuthorization($getInvestigatorFormRequest->visitId, $getInvestigatorFormRequest->currentUserId, $getInvestigatorFormRequest->role, $getInvestigatorFormRequest->studyName);
            $investigatorFormEntity = $this->reviewRepositoryInterface->getInvestigatorForm($getInvestigatorFormRequest->visitId, true);

            $investigatorForm = ReviewEntity::fillFromDBReponseArray($investigatorFormEntity);
            $investigatorForm->setUserDetails( UserEntity::fillOnlyUserIdentification($investigatorFormEntity['user']) );

            $getInvestigatorFormResponse->body = $investigatorForm;
            $getInvestigatorFormResponse->status = 200;
            $getInvestigatorFormResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {

            $getInvestigatorFormResponse->body = $e->getErrorBody();
            $getInvestigatorFormResponse->status = $e->statusCode;
            $getInvestigatorFormResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $visitId, int $currentUserId, string $role, string $studyName)
    {

        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);

        if (!$this->authorizationVisitService->isVisitAllowed($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
