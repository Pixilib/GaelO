<?php

namespace App\GaelO\UseCases\DeleteDocumentation;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class DeleteDocumentation{

    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(DocumentationRepositoryInterface $documentationRepositoryInterface, authorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
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
        $this->authorizationUserService->setUserId($currentUserId);
        if( !$this->authorizationUserService->isRoleAllowed(Constants::ROLE_SUPERVISOR, $studyName)){
            throw new GaelOForbiddenException();
        }
    }
}
