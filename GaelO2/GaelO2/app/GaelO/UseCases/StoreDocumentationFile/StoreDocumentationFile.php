<?php

namespace App\GaelO\UseCases\StoreDocumentationFile;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;
use Exception;

class StoreDocumentationFile{

    public function __construct(PersistenceInterface $documentationRepository, AuthorizationService $authorizationService, TrackerService $trackerService)
    {
        $this->documentationRepository = $documentationRepository;
        $this->authorizationService = $authorizationService;
        $this->trackerService = $trackerService;
    }

    public function execute(StoreDocumentationFileRequest $storeDocumentationFileRequest, StoreDocumentationFileResponse $storeDocumentationFileResponse){

        try{

            $documentationEntity = $this->documentationRepository->getDocumentation($storeDocumentationFileRequest->id);
            $studyName = $documentationEntity['study_name'];
            $this->checkAuthorization($storeDocumentationFileRequest->currentUserId, $studyName);

            if($storeDocumentationFileRequest->contentType !== 'application/pdf'){
                throw new GaelOBadRequestException("Only application/pdf content accepted");
            }

            if( ! $this->is_base64_encoded($storeDocumentationFileRequest->binaryData)){
                throw new GaelOBadRequestException("Payload should be base64 encoded");
            }

            $storagePath = LaravelFunctionAdapter::getStoragePath();

            $destinationPath = $storagePath.'/documentations/'.$studyName;
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            file_put_contents ( $destinationPath.'/'.$documentationEntity['id'].'.pdf', base64_decode($storeDocumentationFileRequest->binaryData) );

            $documentationEntity['path']= $destinationPath.'/'.$documentationEntity['id'].'.pdf';

            $this->documentationRepository->update($storeDocumentationFileRequest->id, $documentationEntity);

            $actionDetails =[
                'documentation_id'=>$storeDocumentationFileRequest->currentUserId,
            ];

            $this->trackerService->writeAction(
                $storeDocumentationFileRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                null,
                Constants::TRACKER_UPLOAD_DOCUMENTATION,
                $actionDetails);

            //Return created documentation ID to help front end to send file data
            $storeDocumentationFileResponse->status = 201;
            $storeDocumentationFileResponse->statusText =  'Created';

        } catch (GaelOException $e){

            $storeDocumentationFileResponse->body = $e->getErrorBody();
            $storeDocumentationFileResponse->status = $e->statusCode;
            $storeDocumentationFileResponse->statusText =  $e->statusText;

        }catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, string $studyName){
        $this->authorizationService->setCurrentUser($currentUserId);
        if( !$this->authorizationService->isRoleAllowed(Constants::ROLE_SUPERVISOR, $studyName)){
            throw new GaelOForbiddenException();
        }
    }

    private function is_base64_encoded($data) : bool {
        if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $data)) {
        return true;
        } else {
        return false;
        }
    }
}
