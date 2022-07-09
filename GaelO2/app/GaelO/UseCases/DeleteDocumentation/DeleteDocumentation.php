<?php

namespace App\GaelO\UseCases\DeleteDocumentation;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class DeleteDocumentation
{

    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(DocumentationRepositoryInterface $documentationRepositoryInterface, AuthorizationStudyService $authorizationStudyService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(DeleteDocumentationRequest $deleteDocumentationRequest, DeleteDocumentationResponse $deleteDocumentationResponse)
    {

        try {

            $currentUserId = $deleteDocumentationRequest->currentUserId;
            $documentationId = $deleteDocumentationRequest->id;

            $documentationEntity = $this->documentationRepositoryInterface->find($documentationId, false);
            $studyName = $documentationEntity['study_name'];

            $this->checkAuthorization($currentUserId, $studyName);

            $this->documentationRepositoryInterface->delete($documentationId);

            $actionDetails = [
                'id' => $documentationId,
                'name' => $documentationEntity['name'],
                'version' => $documentationEntity['version']
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                null,
                Constants::TRACKER_DELETE_DOCUMENTATION,
                $actionDetails
            );

            $deleteDocumentationResponse->status = 200;
            $deleteDocumentationResponse->statusText =  'OK';
        } catch (GaelOException $e) {
            $deleteDocumentationResponse->body = $e->getErrorBody();
            $deleteDocumentationResponse->status = $e->statusCode;
            $deleteDocumentationResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $studyName)
    {
        $this->authorizationStudyService->setStudyName($studyName);
        $this->authorizationStudyService->setUserId($currentUserId);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
