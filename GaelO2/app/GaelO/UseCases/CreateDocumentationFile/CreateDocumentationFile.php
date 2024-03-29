<?php

namespace App\GaelO\UseCases\CreateDocumentationFile;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Util;
use Exception;

class CreateDocumentationFile{

    private AuthorizationStudyService $authorizationStudyService;
    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private FrameworkInterface $frameworkInterface;

    public function __construct(DocumentationRepositoryInterface $documentationRepositoryInterface, AuthorizationStudyService $authorizationStudyService, TrackerRepositoryInterface $trackerRepositoryInterface, FrameworkInterface $frameworkInterface)
    {
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(CreateDocumentationFileRequest $createDocumentationFileRequest, CreateDocumentationFileResponse $createDocumentationFileResponse){

        try{

            $documentationEntity = $this->documentationRepositoryInterface->find($createDocumentationFileRequest->id, false);
            $studyName = $documentationEntity['study_name'];
            $this->checkAuthorization($createDocumentationFileRequest->currentUserId, $studyName);

            if($createDocumentationFileRequest->contentType !== 'application/pdf'){
                throw new GaelOBadRequestException("Only application/pdf content accepted");
            }

            if( ! Util ::isBase64Encoded($createDocumentationFileRequest->binaryData)){
                throw new GaelOBadRequestException("Payload should be base64 encoded");
            }

            $destinationPath = $studyName.'/documentations/'.$documentationEntity['id'].'.pdf';

            $this->frameworkInterface->storeFile( $destinationPath , base64_decode($createDocumentationFileRequest->binaryData));

            $this->documentationRepositoryInterface->updateDocumentationPath($createDocumentationFileRequest->id, $destinationPath);

            $actionDetails =[
                'documentation_id'=>$createDocumentationFileRequest->id,
            ];

            $this->trackerRepositoryInterface->writeAction(
                $createDocumentationFileRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                null,
                Constants::TRACKER_UPLOAD_DOCUMENTATION,
                $actionDetails);

            //Return created documentation ID to help front end to send file data
            $createDocumentationFileResponse->status = 201;
            $createDocumentationFileResponse->statusText =  'Created';

        } catch (AbstractGaelOException $e){

            $createDocumentationFileResponse->body = $e->getErrorBody();
            $createDocumentationFileResponse->status = $e->statusCode;
            $createDocumentationFileResponse->statusText =  $e->statusText;

        }catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, string $studyName){
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if( !$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)){
            throw new GaelOForbiddenException();
        }
    }


}
