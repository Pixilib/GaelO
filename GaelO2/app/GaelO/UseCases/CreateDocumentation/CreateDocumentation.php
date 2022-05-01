<?php

namespace App\GaelO\UseCases\CreateDocumentation;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class CreateDocumentation {

    private AuthorizationStudyService $authorizationStudyService;
    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(DocumentationRepositoryInterface $documentationRepositoryInterface, AuthorizationStudyService $authorizationStudyService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(CreateDocumentationRequest $createDocumentationRequest, CreateDocumentationResponse $createDocumentationResponse){

        try{
            $this->checkAuthorization($createDocumentationRequest->currentUserId, $createDocumentationRequest->studyName);

            if($this->documentationRepositoryInterface->isKnowndocumentation($createDocumentationRequest->name, $createDocumentationRequest->version)){
                throw new GaelOConflictException("Documentation already existing under this version");
            };

            $createdEntity = $this->documentationRepositoryInterface->createDocumentation(
                $createDocumentationRequest->name,
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
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if( !$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)){
            throw new GaelOForbiddenException();
        }

    }

}