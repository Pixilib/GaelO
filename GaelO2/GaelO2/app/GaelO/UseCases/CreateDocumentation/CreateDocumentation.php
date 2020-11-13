<?php

namespace App\GaelO\UseCases\CreateDocumentation;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;
use App\GaelO\Util;
use Exception;

class CreateDocumentation {

    public function __construct(PersistenceInterface $documentationRepository, AuthorizationService $authorizationService, TrackerService $trackerService)
    {
        $this->documentationRepository = $documentationRepository;
        $this->authorizationService = $authorizationService;
        $this->trackerService = $trackerService;
    }

    public function execute(CreateDocumentationRequest $createDocumentationRequest, CreateDocumentationResponse $createDocumentationResponse){

        try{
            $this->checkAuthorization($createDocumentationRequest->currentUserId, $createDocumentationRequest->studyName);

            $createdEntity = $this->documentationRepository->createDocumentation(
                $createDocumentationRequest->name,
                Util::now(),
                $createDocumentationRequest->studyName,
                $createDocumentationRequest->version,
                $createDocumentationRequest->investigator,
                $createDocumentationRequest->controller,
                $createDocumentationRequest->monitor,
                $createDocumentationRequest->reviewer
            );

            $actionDetails =[
                'documentation_id'=>$createdEntity['id'],
                'name'=>$createDocumentationRequest->studyName,
                'version'=>$createDocumentationRequest->version,
                'investigator'=>$createDocumentationRequest->investigator,
                'controller'=>$createDocumentationRequest->controller,
                'monitor'=>$createDocumentationRequest->monitor,
                'reviewer'=>$createDocumentationRequest->reviewer
            ];

            $this->trackerService->writeAction(
                $createDocumentationRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $createDocumentationRequest->studyName,
                null,
                Constants::TRACKER_ADD_DOCUMENTATION,
                $actionDetails);

            //Return created documentation ID to help front end to send file data
            $createDocumentationResponse->body = ['id'=>$createdEntity['id']];
            $createDocumentationResponse->status = 201;
            $createDocumentationResponse->statusText =  'Created';

        } catch (GaelOException $e){

            $createDocumentationResponse->body = $e->getErrorBody();
            $createDocumentationResponse->status = $e->statusCode;
            $createDocumentationResponse->statusText =  $e->statusText;

        }catch (Exception $e){
            throw $e;
        }

        //Retourne l'id pour pouvoir uploader le fichier via un POST sur l'URI /file
        //Lors de upload : Check Extension autorisée  + check limit upload
        //Utiliser service laraval pour stoker le fichier via un adapter
        //https://laravel.com/docs/8.x/filesystem#file-uploads
    }

    public function checkAuthorization(int $currentUserId, string $studyName){
        $this->authorizationService->setCurrentUser($currentUserId);
        if( !$this->authorizationService->isRoleAllowed(Constants::ROLE_SUPERVISOR, $studyName)){
            throw new GaelOForbiddenException();
        }

    }

}
