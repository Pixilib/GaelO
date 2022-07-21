<?php

namespace App\GaelO\UseCases\CreateDocumentation;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Util;
use Exception;

class CreateDocumentation
{

    private AuthorizationStudyService $authorizationStudyService;
    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(DocumentationRepositoryInterface $documentationRepositoryInterface, AuthorizationStudyService $authorizationStudyService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(CreateDocumentationRequest $createDocumentationRequest, CreateDocumentationResponse $createDocumentationResponse)
    {

        try {

            $currentUserId = $createDocumentationRequest->currentUserId;
            $studyName = $createDocumentationRequest->studyName;
            $documentationName = $createDocumentationRequest->name;
            $documentationVersion = $createDocumentationRequest->version;

            if(!Util::isSemanticVersioning($documentationVersion))
            {
                throw new GaelOBadRequestException('documentation version shall be in semantic versioning major.minor.patch ex: 1.2.0');
            }

            $this->checkAuthorization($currentUserId, $studyName);

            if ($this->documentationRepositoryInterface->isKnowndocumentation($studyName, $documentationName, $documentationVersion)) {
                throw new GaelOConflictException("Documentation already existing under this version");
            };

            $createdEntity = $this->documentationRepositoryInterface->createDocumentation(
                $documentationName,
                $studyName,
                $documentationVersion,
                $createDocumentationRequest->investigator,
                $createDocumentationRequest->controller,
                $createDocumentationRequest->monitor,
                $createDocumentationRequest->reviewer
            );

            $actionDetails = [
                'documentation_id' => $createdEntity['id'],
                'name' => $documentationName,
                'version' => $documentationVersion,
                'investigator' => $createDocumentationRequest->investigator,
                'controller' => $createDocumentationRequest->controller,
                'monitor' => $createDocumentationRequest->monitor,
                'reviewer' => $createDocumentationRequest->reviewer
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                null,
                Constants::TRACKER_ADD_DOCUMENTATION,
                $actionDetails
            );

            //Return created documentation ID to help front end to send file data
            $createDocumentationResponse->body = ['id' => $createdEntity['id']];
            $createDocumentationResponse->status = 201;
            $createDocumentationResponse->statusText =  'Created';
        } catch (GaelOException $e) {
            $createDocumentationResponse->body = $e->getErrorBody();
            $createDocumentationResponse->status = $e->statusCode;
            $createDocumentationResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function checkAuthorization(int $currentUserId, string $studyName)
    {
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}
