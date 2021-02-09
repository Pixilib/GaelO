<?php

namespace App\GaelO\UseCases\ModifyDocumentation;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class ModifyDocumentation {

    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private AuthorizationService $authorizationService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(DocumentationRepositoryInterface $documentationRepositoryInterface, AuthorizationService $authorizationService, TrackerRepositoryInterface $trackerRepositoryInterface){
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationService = $authorizationService;
     }

    public function execute(ModifyDocumentationRequest $modifyDocumentationRequest, ModifyDocumentationResponse $modifyDocumentationResponse){
        try{

            $documentation = $this->documentationRepositoryInterface->find($modifyDocumentationRequest->id);
            $studyName = $documentation['study_name'];

            $this->checkAuthorization($modifyDocumentationRequest->currentUserId, $studyName);
            //Fill missing fields with known info from the database
            //Change given fields 
            if(!empty($modifyDocumentationRequest->documentDate)) $documentation['document_date'] = $modifyDocumentationRequest->documentDate;
            if(!empty($modifyDocumentationRequest->version)) {
                $documentation['version'] = $modifyDocumentationRequest->version;
                if($this->documentationRepositoryInterface->isKnowndocumentation($documentation['name'], $modifyDocumentationRequest->version)){
                    throw new GaelOConflictException("Documentation already existing under this version");
                };
            }

            if($modifyDocumentationRequest->investigator !== null) $documentation['investigator'] = $modifyDocumentationRequest->investigator;
            if($modifyDocumentationRequest->controller !== null) $documentation['controller'] = $modifyDocumentationRequest->controller;
            if($modifyDocumentationRequest->monitor !== null) $documentation['monitor'] = $modifyDocumentationRequest->monitor;
            if($modifyDocumentationRequest->reviewer !== null) $documentation['reviewer'] = $modifyDocumentationRequest->reviewer;

            $this->documentationRepositoryInterface->updateDocumentation(
                $documentation['id'], 
                $documentation['name'], 
                $documentation['document_date'], 
                $documentation['study_name'], 
                $documentation['version'], 
                $documentation['investigator'],
                $documentation['controller'], 
                $documentation['monitor'], 
                $documentation['reviewer']);

            $actionDetails = $documentation;

            $this->trackerRepositoryInterface->writeAction($modifyDocumentationRequest->currentUserId, Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_UPDATE_DOCUMENTATION, $actionDetails);

            $modifyDocumentationResponse->status = 200;
            $modifyDocumentationResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $modifyDocumentationResponse->body = $e->getErrorBody();
            $modifyDocumentationResponse->status = $e->statusCode;
            $modifyDocumentationResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $studyName){
        $this->authorizationService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        if(!$this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        }
    }
}
