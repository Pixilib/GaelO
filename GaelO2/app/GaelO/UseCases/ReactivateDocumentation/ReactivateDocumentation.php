<?php

namespace App\GaelO\UseCases\ReactivateDocumentation;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class ReactivateDocumentation
{

    private AuthorizationStudyService $authorizationStudyService;
    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(AuthorizationStudyService $authorizationStudyService, DocumentationRepositoryInterface $documentationRepositoryInterface, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ReactivateDocumentationRequest $reactivateDocumentationRequest, ReactivateDocumentationResponse $reactivateDocumentationResponse)
    {

        try {

            $documentationEntity = $this->documentationRepositoryInterface->find($reactivateDocumentationRequest->documentationId, true);
            $studyName = $documentationEntity['study_name'];
            $currentUserId = $reactivateDocumentationRequest->currentUserId;

            $this->checkAuthorization($currentUserId, $studyName);


            //Change dicom study Activation
            $this->documentationRepositoryInterface->reactivateDocumentation($documentationEntity['id']);

            //Tracker
            $actionDetails = [
                'documentation_id' => $documentationEntity['id'],
                'name' => $documentationEntity['name'],
                'version' => $documentationEntity['version'],
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                null,
                Constants::TRACKER_REACTIVATE_DOCUMENTATION,
                $actionDetails
            );
            $reactivateDocumentationResponse->status = 200;
            $reactivateDocumentationResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $reactivateDocumentationResponse->status = $e->statusCode;
            $reactivateDocumentationResponse->statusText = $e->statusText;
            $reactivateDocumentationResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function checkAuthorization(int $currentUserId, string $studyName): void
    {
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
