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

        header('Content-Disposition: attachment; filename="Dicom-GaelO.zip"');

        $response = $this->orthancService->getOrthancZipStream($orthancSeriesIDs);
        $getDicomsResponse->status = 200;
        $getDicomsResponse->statusText = 'OK';
        $getDicomsResponse->body = $response;
    }

}
