<?php

namespace App\GaelO\UseCases\GetStudy;

use App\GaelO\Interfaces\PersistenceInterface;

class GetStudy{

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;

    }

    public function execute(GetStudyRequest $getStudyRequest, GetStudyResponse $getStudyResponse) : void{
        $studies = $this->persistenceInterface->getStudies();

        $getStudyResponse->body = $studies;
        $getStudyResponse->status = 200;
        $getStudyResponse->statusText = 'OK';

    }

}
