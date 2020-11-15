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
        //Checker Authorization
        //Recupérer dans la DB la liste des series ID d'Orthanc a telecharger
        //Ici en deux temps, le response donne le nom du dicom et le outputStream fait l'output
        $this->orthancSeriesIDs = ["a66b93bf-d6bb38ab-9b53f65b-e9c39913-8b2969db"];
        $getDicomsResponse->filename = 'DicomTestSalimVisit1.zip';

    }

    public function outputStream(){
        $this->orthancService->getOrthancZipStream($this->orthancSeriesIDs);
    }

}
