<?php

namespace App\GaelO\UseCases\GetDocumentation;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\DocumentationEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetDocumentation
{

    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(DocumentationRepositoryInterface $documentationRepositoryInterface, AuthorizationStudyService $authorizationStudyService)
    {
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(GetDocumentationRequest $getDocumentationRequest, GetDocumentationResponse $getDocumentationResponse)
    {
        try {

            $currentUserId = $getDocumentationRequest->currentUserId;
            $role = $getDocumentationRequest->role;
            $studyName = $getDocumentationRequest->studyName;

            $this->checkAuthorization($currentUserId, $role, $studyName);

            $answersArray = [];
            if ($role === Constants::ROLE_SUPERVISOR) {
                $answersArray = $this->documentationRepositoryInterface->getDocumentationsOfStudy($studyName, true);
            } else {
                $answersArray = $this->documentationRepositoryInterface->getDocumentationOfStudyWithRole($studyName, $role);
            }

            $entitiesArray = [];
            foreach ($answersArray as $answer) {
                $entitiesArray[] = DocumentationEntity::fillFromDBReponseArray($answer);
            }

            $getDocumentationResponse->body = $entitiesArray;
            $getDocumentationResponse->status = 200;
            $getDocumentationResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $getDocumentationResponse->body = $e->getErrorBody();
            $getDocumentationResponse->status = $e->statusCode;
            $getDocumentationResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $role, string $studyName)
    {
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy($role)) {
            throw new GaelOForbiddenException();
        }
    }
}
