<?php

namespace App\GaelO\UseCases\CreateDocumentation;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;

class CreateDocumentation {

    public function __construct(PersistenceInterface $documentationRepository, AuthorizationService $authorizationService, TrackerService $trackerService)
    {
        $this->documentationRepository = $documentationRepository;
        $this->authorizationService = $authorizationService;
        $this->trackerService = $trackerService;
    }

    public function execute(CreateDocumentationRequest $createDocumentationRequest, CreateDocumentationResponse $createDocumentationResponse){
        //Uniquement supervisor de l'etude
        //Inscription BDD
        //Tracker
        //Retourne l'id pour pouvoir uploader le fichier via un POST sur l'URI /file
        //Lors de upload : Check Extension autorisée  + check limit upload
        //Utiliser service laraval pour stoker le fichier via un adapter
        //https://laravel.com/docs/8.x/filesystem#file-uploads
    }

}
