<?php

namespace App\GaelO\UseCases\GetDocumentationFile;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetDocumentationFile{

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService)
    {
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetDocumentationFileRequest $getDocumentationFileRequest, GetDocumentationFileResponse $getdocumentationFileReponse){
        try{

            $documentationData = $this->persistenceInterface->find($getDocumentationFileRequest->id);

            $documentationAllowedRoles = [];

            if($documentationData['investigator']) $documentationAllowedRoles[]= Constants::ROLE_INVESTIGATOR;
            if($documentationData['monitor']) $documentationAllowedRoles[]= Constants::ROLE_CONTROLLER;
            if($documentationData['controller']) $documentationAllowedRoles[]= Constants::ROLE_CONTROLLER;
            if($documentationData['reviewer']) $documentationAllowedRoles[]= Constants::ROLE_REVIEWER;

            $this->checkAuthorization($getDocumentationFileRequest->currentUserId, $documentationData['study_name'], $documentationAllowedRoles );

            $getdocumentationFileReponse->status = 200;
            $getdocumentationFileReponse->statusText = 'OK';
            $getdocumentationFileReponse->filePath = LaravelFunctionAdapter::getStoragePath().$documentationData['path'];
            $getdocumentationFileReponse->filename = $documentationData['name'].'_'.$documentationData['version'].'.pdf';

        } catch (GaelOException $e){
            $getdocumentationFileReponse->status = $e->statusCode;
            $getdocumentationFileReponse->statusText = $e->statusText;
            $getdocumentationFileReponse->body = $e->getErrorBody();

        } catch (Exception $e){

            throw $e;

        }

    }

    private function checkAuthorization($currentUserId, $studyName, $documentationAllowedRoles){
        $this->authorizationService->setCurrentUserAndRole($currentUserId);
        if ( ! $this->authorizationService->isOneOfRolesAllowed($documentationAllowedRoles, $studyName)){
            throw new GaelOForbiddenException();
        }
    }
}
