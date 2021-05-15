<?php

namespace App\GaelO\UseCases\GetDocumentation;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\DocumentationEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\DocumentationRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetDocumentation {

    private DocumentationRepositoryInterface $documentationRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(DocumentationRepositoryInterface $documentationRepositoryInterface, AuthorizationService $authorizationService)
    {
        $this->documentationRepositoryInterface = $documentationRepositoryInterface;
        $this->authorizationService = $authorizationService;

    }

    public function execute(GetDocumentationRequest $getDocumentationRequest, GetDocumentationResponse $getDocumentationResponse){
        try{

            $this->checkAuthorization($getDocumentationRequest->currentUserId, $getDocumentationRequest->role, $getDocumentationRequest->studyName);

            $answersArray = [] ;

            if($getDocumentationRequest->role === Constants::ROLE_SUPERVISOR){
                $answersArray = $this->documentationRepositoryInterface->getDocumentationsOfStudy($getDocumentationRequest->studyName);
            }else{
                $answersArray = $this->documentationRepositoryInterface->getDocumentationOfStudyWithRole($getDocumentationRequest->studyName, $getDocumentationRequest->role);
            }

            $entitiesArray = [];

            foreach($answersArray as $answer){
                $entitiesArray[] = DocumentationEntity::fillFromDBReponseArray($answer);
            }

            $getDocumentationResponse->body = $entitiesArray;
            $getDocumentationResponse->status = 200;
            $getDocumentationResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $getDocumentationResponse->body = $e->getErrorBody();
            $getDocumentationResponse->status = $e->statusCode;
            $getDocumentationResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $role, string $studyName){
        $this->authorizationService->setCurrentUserAndRole($currentUserId, $role);
        if(!$this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        };

    }
}
