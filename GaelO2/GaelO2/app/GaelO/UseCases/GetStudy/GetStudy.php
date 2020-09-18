<?php

namespace App\GaelO\UseCases\GetStudy;

use App\GaelO\Interfaces\PersistenceInterface;

class GetStudy{

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;

    }

    public function execute(GetStudyRequest $getStudyRequest, GetStudyResponse $getStudyResponse) : void{
        $studies = $this->persistenceInterface->getStudies(true);

        $responseArray = [];
        foreach($studies as $study){
            $responseArray[] = StudyEntity::fillFromDBReponseArray($study);
        }

        if($getStudyRequest->expand){
            //SK ICI AJOUTER LES DATA VISIT GROUP ET VISIT TYPE
            //Dans le meme use case mettre le retrieve de toute les study ou d'une seule avec les details?
        }

        $getStudyResponse->body = $responseArray;
        $getStudyResponse->status = 200;
        $getStudyResponse->statusText = 'OK';

    }

}
