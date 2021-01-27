<?php

namespace App\GaelO\UseCases\CreateDocumentation;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Util;
use Exception;

class CreateDocumentation {

    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(DocumentationRepositoryInterface $documentationRepositoryInterface, AuthorizationService $authorizationService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->authorizationService = $authorizationService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(CreateDocumentationRequest $createDocumentationRequest, CreateDocumentationResponse $createDocumentationResponse){

        try{
            $this->checkAuthorization($createDocumentationRequest->currentUserId, $createDocumentationRequest->studyName);

            $createdEntity = $this->documentationRepositoryInterface->createDocumentation(
                $createDocumentationRequest->name,
                Util::now(),
                $createDocumentationRequest->studyName,
                $createDocumentationRequest->version,
                $createDocumentationRequest->investigator,
                $createDocumentationRequest->controller,
                $createDocumentationRequest->monitor,
                $createDocumentationRequest->reviewer
            );

            $actionDetails =[
                'documentation_id'=>$createdEntity['id'],
                'name'=>$createDocumentationRequest->studyName,
                'version'=>$createDocumentationRequest->version,
                'investigator'=>$createDocumentationRequest->investigator,
                'controller'=>$createDocumentationRequest->controller,
                'monitor'=>$createDocumentationRequest->monitor,
                'reviewer'=>$createDocumentationRequest->reviewer
            ];

            $this->trackerRepositoryInterface->writeAction(
                $createDocumentationRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $createDocumentationRequest->studyName,
                null,
                Constants::TRACKER_ADD_DOCUMENTATION,
                $actionDetails);

            //Return created documentation ID to help front end to send file data
            $createDocumentationResponse->body = ['id'=>$createdEntity['id']];
            $createDocumentationResponse->status = 201;
            $createDocumentationResponse->statusText =  'Created';

        } catch (GaelOException $e){

            $createDocumentationResponse->body = $e->getErrorBody();
            $createDocumentationResponse->status = $e->statusCode;
            $createDocumentationResponse->statusText =  $e->statusText;

        }catch (Exception $e){
            throw $e;
        }

    }

    public function checkAuthorization(int $currentUserId, string $studyName){
        $this->authorizationService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        if( !$this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        }

    }

}
