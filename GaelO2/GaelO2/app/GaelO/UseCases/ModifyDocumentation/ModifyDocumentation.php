<?php

namespace App\GaelO\UseCases\ModifyDocumentation;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class ModifyDocumentation {

    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(DocumentationRepositoryInterface $documentationRepositoryInterface, AuthorizationStudyService $authorizationStudyService, TrackerRepositoryInterface $trackerRepositoryInterface){
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
     }

    public function execute(ModifyDocumentationRequest $modifyDocumentationRequest, ModifyDocumentationResponse $modifyDocumentationResponse){
        try{

            $documentation = $this->documentationRepositoryInterface->find($modifyDocumentationRequest->id);
            $studyName = $documentation['study_name'];

            $this->checkAuthorization($modifyDocumentationRequest->currentUserId, $studyName);

            //Update data according to request
            $documentation['investigator'] = $modifyDocumentationRequest->investigator;
            $documentation['controller'] = $modifyDocumentationRequest->controller;
            $documentation['monitor'] = $modifyDocumentationRequest->monitor;
            $documentation['reviewer'] = $modifyDocumentationRequest->reviewer;

            //In case of version change, check for conflicts
            if($modifyDocumentationRequest->version !==  $documentation['version']) {
                if($this->documentationRepositoryInterface->isKnowndocumentation($documentation['name'], $modifyDocumentationRequest->version)){
                    throw new GaelOConflictException("Documentation already existing under this version");
                };
                $documentation['version'] = $modifyDocumentationRequest->version;
            }

            $this->documentationRepositoryInterface->updateDocumentation(
                $documentation['id'],
                $documentation['name'],
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
        $this->authorizationStudyService->setStudyName($studyName);
        $this->authorizationStudyService->setUserId($currentUserId);
        if(!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)){
            throw new GaelOForbiddenException();
        }
    }
}
