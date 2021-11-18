<?php

namespace App\GaelO\UseCases\GetReviewsMetadataFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Repositories\VisitGroupRepository;
use App\GaelO\Repositories\VisitTypeRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetReviewsMetadataFromVisitType {

    private AuthorizationUserService $authorizationUserService;
    private VisitTypeRepository $visitTypeRepository;
    private VisitGroupRepository $visitGroupRepository;
    private FrameworkInterface $frameworkInterface;

    public function __construct(AuthorizationUserService $authorizationUserService, VisitTypeRepository $visitTypeRepository, VisitGroupRepository $visitGroupRepository, FrameworkInterface $frameworkInterface)
    {
        $this->authorizationUserService = $authorizationUserService;
        $this->visitTypeRepository = $visitTypeRepository;
        $this->visitGroupRepository = $visitGroupRepository;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(GetReviewsMetadataFromVisitTypeRequest $getReviewsMetadataFromVisitTypeRequest, GetReviewsMetadataFromVisitTypeResponse $getReviewsMetadataFromVisitTypeResponse)
    {
        try{

            $studyName = $getReviewsMetadataFromVisitTypeRequest->studyName;

            //SK ICI VERIFIER QUE VISIT TYPE EST BIEN DANS UNE STUDY PERMISE
            $this->checkAuthorization($getReviewsMetadataFromVisitTypeRequest->currentUserId, $studyName);

            $visitTypeEntity = $this->visitTypeRepository->find($getReviewsMetadataFromVisitTypeRequest->visitTypeId);

            $visitGroupEntity = $this->visitGroupRepository->find($visitTypeEntity['visit_group_id']);

            $abstractStudyRules = $this->frameworkInterface->make('\App\GaelO\Services\SpecificStudiesRules\\' . $studyName . '_' . $visitGroupEntity['modality'] . '_' . $visitTypeEntity['name']);

            $answer = [];
            $answer['default'] = $abstractStudyRules->getReviewerValidationRules(false);
            $answer['adjudication'] = $abstractStudyRules->getReviewerValidationRules(true);


            $getReviewsMetadataFromVisitTypeResponse->body = $answer;
            $getReviewsMetadataFromVisitTypeResponse->status = 200;
            $getReviewsMetadataFromVisitTypeResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $getReviewsMetadataFromVisitTypeResponse->body = $e->getErrorBody();
            $getReviewsMetadataFromVisitTypeResponse->status = $e->statusCode;
            $getReviewsMetadataFromVisitTypeResponse->statusText = $e->statusText;

        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $studyName)
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isRoleAllowed(Constants::ROLE_SUPERVISOR, $studyName)) {
            throw new GaelOForbiddenException();
        };
    }

}
