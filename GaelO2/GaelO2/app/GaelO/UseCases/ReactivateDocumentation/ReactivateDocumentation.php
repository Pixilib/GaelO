<?php

namespace App\GaelO\UseCases\ReactivateDocumentation;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class ReactivateDocumentation {

    private AuthorizationService $authorizationService;
    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(AuthorizationService $authorizationService, DocumentationRepositoryInterface $documentationRepositoryInterface, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->authorizationService = $authorizationService;
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ReactivateDocumentationRequest $reactivateDocumentationRequest, ReactivateDocumentationResponse $reactivateDocumentationResponse){

        try{

            $documentationEntity = $this->documentationRepositoryInterface->find($reactivateDocumentationRequest->documentationId);
            $studyName = $documentationEntity['study_name'];

            $this->checkAuthorization($reactivateDocumentationRequest->currentUserId, $studyName);


            //Change dicom study Activation
            $this->documentationRepositoryInterface->reactivateDocumentation($documentationEntity['id']);

            //Tracker
            $actionDetails = [
                'documentationId'=>$documentationEntity['id'],
                'name' => $documentationEntity['name'],
                'version' => $documentationEntity['version'],
            ];

            $this->trackerRepositoryInterface->writeAction(
                $reactivateDocumentationRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                null,
                Constants::TRACKER_REACTIVATE_DOCUMENTATION,
                $actionDetails
            );


            $reactivateDocumentationResponse->status = 200;
            $reactivateDocumentationResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $reactivateDocumentationResponse->status = $e->statusCode;
            $reactivateDocumentationResponse->statusText = $e->statusText;
            $reactivateDocumentationResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }

    }

    public function checkAuthorization(int $currentUserId, string $studyName) : void {

        $this->authorizationService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        if ( ! $this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        }

    }

}
