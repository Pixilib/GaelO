<?php

namespace App\GaelO\UseCases\DeleteDocumentation;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class DeleteDocumentation{

    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private AuthorizationService $authorizationService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(DocumentationRepositoryInterface $documentationRepositoryInterface, AuthorizationService $authorizationService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->authorizationService = $authorizationService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(DeleteDocumentationRequest $deleteDocumentationRequest, DeleteDocumentationResponse $deleteDocumentationResponse){

        try{

            $documentationEntity = $this->documentationRepositoryInterface->find($deleteDocumentationRequest->id);
            $studyName = $documentationEntity['study_name'];

            $this->checkAuthorization($deleteDocumentationRequest->currentUserId, $studyName);

            $this->documentationRepositoryInterface->delete($deleteDocumentationRequest->id);

            $actionDetails = [
                'documentationId' => $deleteDocumentationRequest->id,
                'documenationName'=> $documentationEntity['name'],
                'documenationVersion'=> $documentationEntity['version']
            ];

            $this->trackerRepositoryInterface->writeAction(
                $deleteDocumentationRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                null,
                Constants::TRACKER_DELETE_DOCUMENTATION,
                $actionDetails);

                $deleteDocumentationResponse->status = 200;
                $deleteDocumentationResponse->statusText =  'OK';


        } catch (GaelOException $e){

            $deleteDocumentationResponse->body = $e->getErrorBody();
            $deleteDocumentationResponse->status = $e->statusCode;
            $deleteDocumentationResponse->statusText =  $e->statusText;

        } catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $studyName){
        $this->authorizationService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        if( !$this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        }
    }
}
