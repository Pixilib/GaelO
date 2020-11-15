<?php

namespace App\GaelO\UseCases\GetDicoms;

use App\GaelO\Services\OrthancService;

class GetDicoms{

    public function __construct(OrthancService $orthancService)
    {
        $this->orthancService = $orthancService;
        $this->orthancService->setOrthancServer(false);
    }

    public function execute(GetDicomsRequest $getDicomsRequest, GetDicomsResponse $getDicomsResponse){
        $orthancSeriesIDs = ["a66b93bf-d6bb38ab-9b53f65b-e9c39913-8b2969db"];
        $this->orthancService->getOrthancZipStream($orthancSeriesIDs);

    }

}
