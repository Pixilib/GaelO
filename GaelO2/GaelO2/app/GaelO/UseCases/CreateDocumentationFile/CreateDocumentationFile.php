<?php

namespace App\GaelO\UseCases\CreateDocumentationFile;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use App\GaelO\Util;
use Exception;

class CreateDocumentationFile{

    private AuthorizationUserService $authorizationUserService;
    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private FrameworkInterface $frameworkInterface;

    public function __construct(DocumentationRepositoryInterface $documentationRepositoryInterface, AuthorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface, FrameworkInterface $frameworkInterface)
    {
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(CreateDocumentationFileRequest $createDocumentationFileRequest, CreateDocumentationFileResponse $createDocumentationFileResponse){

        try{

            $documentationEntity = $this->documentationRepositoryInterface->find($createDocumentationFileRequest->id);
            $studyName = $documentationEntity['study_name'];
            $this->checkAuthorization($createDocumentationFileRequest->currentUserId, $studyName);

            if($createDocumentationFileRequest->contentType !== 'application/pdf'){
                throw new GaelOBadRequestException("Only application/pdf content accepted");
            }

            if( ! Util ::is_base64_encoded($createDocumentationFileRequest->binaryData)){
                throw new GaelOBadRequestException("Payload should be base64 encoded");
            }

            $storagePath = $this->frameworkInterface::getStoragePath();

            $destinationPath = $storagePath.'/'.$studyName.'/documentations';
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            file_put_contents ( $destinationPath.'/'.$documentationEntity['id'].'.pdf', base64_decode($createDocumentationFileRequest->binaryData) );

            $relativePath= '/'.$studyName.'/documentations/'.$documentationEntity['id'].'.pdf';

            $this->documentationRepositoryInterface->updateDocumentationPath($createDocumentationFileRequest->id, $relativePath);

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

        } catch (GaelOException $e){

            $createDocumentationFileResponse->body = $e->getErrorBody();
            $createDocumentationFileResponse->status = $e->statusCode;
            $createDocumentationFileResponse->statusText =  $e->statusText;

        }catch (Exception $e){
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
