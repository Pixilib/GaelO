<?php

namespace App\GaelO\UseCases\GetDocumentationFile;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetDocumentationFile
{

    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(DocumentationRepositoryInterface $documentationRepositoryInterface, AuthorizationUserService $authorizationUserService)
    {
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(GetDocumentationFileRequest $getDocumentationFileRequest, GetDocumentationFileResponse $getdocumentationFileReponse)
    {
        try {

            $documentationData = $this->documentationRepositoryInterface->find($getDocumentationFileRequest->id, false);

            $documentationAllowedRoles = [];

            if ($documentationData['investigator']) $documentationAllowedRoles[] = Constants::ROLE_INVESTIGATOR;
            if ($documentationData['monitor']) $documentationAllowedRoles[] = Constants::ROLE_CONTROLLER;
            if ($documentationData['controller']) $documentationAllowedRoles[] = Constants::ROLE_CONTROLLER;
            if ($documentationData['reviewer']) $documentationAllowedRoles[] = Constants::ROLE_REVIEWER;

            $this->checkAuthorization($getDocumentationFileRequest->currentUserId, $documentationData['study_name'], $documentationAllowedRoles);

            $getdocumentationFileReponse->status = 200;
            $getdocumentationFileReponse->statusText = 'OK';
            $getdocumentationFileReponse->filePath = $documentationData['path'];
            $getdocumentationFileReponse->filename = $documentationData['name'] . '_' . $documentationData['version'] . '.pdf';
        } catch (AbstractGaelOException $e) {
            $getdocumentationFileReponse->status = $e->statusCode;
            $getdocumentationFileReponse->statusText = $e->statusText;
            $getdocumentationFileReponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization($currentUserId, $studyName, $documentationAllowedRoles)
    {
        $this->authorizationUserService->setUserId($currentUserId);
        //Disallowed if user not supervisor and not in the granted role for this documentation
        if (!$this->authorizationUserService->isRoleAllowed(Constants::ROLE_SUPERVISOR, $studyName) && !$this->authorizationUserService->isOneOfRoleAllowed($documentationAllowedRoles, $studyName)) {
            throw new GaelOForbiddenException();
        }
    }
}
