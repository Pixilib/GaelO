<?php

namespace App\GaelO\UseCases\GetReviewsMetadataFromVisitType;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Repositories\VisitGroupRepository;
use App\GaelO\Repositories\VisitTypeRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use App\GaelO\Services\FormService\FormService;
use Exception;

class GetReviewsMetadataFromVisitType {

    private AuthorizationStudyService $authorizationStudyService;
    private VisitTypeRepository $visitTypeRepository;
    private VisitGroupRepository $visitGroupRepository;
    private FormService $formService;

    public function __construct(AuthorizationStudyService $authorizationStudyService, VisitTypeRepository $visitTypeRepository, VisitGroupRepository $visitGroupRepository, FormService $formService)
    {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->visitTypeRepository = $visitTypeRepository;
        $this->visitGroupRepository = $visitGroupRepository;
        $this->formService = $formService;
    }

    public function execute(GetReviewsMetadataFromVisitTypeRequest $getReviewsMetadataFromVisitTypeRequest, GetReviewsMetadataFromVisitTypeResponse $getReviewsMetadataFromVisitTypeResponse)
    {
        try{

            $studyName = $getReviewsMetadataFromVisitTypeRequest->studyName;

            $this->checkAuthorization($getReviewsMetadataFromVisitTypeRequest->currentUserId, $studyName);

            $visitTypeEntity = $this->visitTypeRepository->find($getReviewsMetadataFromVisitTypeRequest->visitTypeId);

            $visitGroupEntity = $this->visitGroupRepository->find($visitTypeEntity['visit_group_id']);

            $abstractStudyRules = $this->formService->getSpecificStudiesRules($studyName, $visitGroupEntity['modality'], $visitTypeEntity['name']);

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
        $this->authorizationStudyService->setUserId($userId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        };
    }

}
