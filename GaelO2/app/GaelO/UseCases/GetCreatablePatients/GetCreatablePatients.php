<?php

namespace App\GaelO\UseCases\GetCreatablePatients;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\GaelOStudiesService\AbstractGaelOStudy;
use Exception;

class GetCreatablePatients
{

    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(AuthorizationStudyService $authorizationStudyService)
    {
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(GetCreatablePatientsRequest $getCreatablePatientsRequest, GetCreatablePatientsResponse $getCreatablePatientsResponse)
    {
        try {

            $studyName = $getCreatablePatientsRequest->studyName;
            $role = $getCreatablePatientsRequest->role;
            $currentUserId = $getCreatablePatientsRequest->currentUserId;

            $this->checkAuthorization($currentUserId, $studyName, $role);

            $studyRule = AbstractGaelOStudy::getSpecificStudyObject($studyName);
            $answer = $studyRule->getCreatablePatientsCode();

            $getCreatablePatientsResponse->body = $answer;
            $getCreatablePatientsResponse->status = 200;
            $getCreatablePatientsResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getCreatablePatientsResponse->body = $e->getErrorBody();
            $getCreatablePatientsResponse->status = $e->statusCode;
            $getCreatablePatientsResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $studyName, string $role)
    {
        if (!in_array($role, [Constants::ROLE_INVESTIGATOR, Constants::ROLE_SUPERVISOR])) {
            throw new GaelOForbiddenException();
        };
        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
