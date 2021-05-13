<?php

namespace App\GaelO\UseCases\CreateDocumentationFile;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Util;
use Exception;

class CreateDocumentationFile{

    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(DocumentationRepositoryInterface $documentationRepositoryInterface, AuthorizationService $authorizationService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->authorizationService = $authorizationService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
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

            $storagePath = LaravelFunctionAdapter::getStoragePath();

            $destinationPath = '/documentations/'.$studyName;
            if (!is_dir($storagePath.'/'.$destinationPath)) {
                mkdir($storagePath.'/'.$destinationPath, 0755, true);
            }

            file_put_contents ( $storagePath.'/'.$destinationPath.'/'.$documentationEntity['id'].'.pdf', base64_decode($createDocumentationFileRequest->binaryData) );

            $documentationEntity['path']= $destinationPath.'/'.$documentationEntity['id'].'.pdf';

            $this->documentationRepositoryInterface->update($createDocumentationFileRequest->id, $documentationEntity);

            $actionDetails =[
                'documentation_id'=>$createDocumentationFileRequest->currentUserId,
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
        $this->authorizationService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        if( !$this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        }
    }


}
