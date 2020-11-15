<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetDicoms\GetDicoms;
use App\GaelO\UseCases\GetDicoms\GetDicomsRequest;
use App\GaelO\UseCases\GetDicoms\GetDicomsResponse;
use Illuminate\Http\Request;

class DicomController extends Controller
{
    /**
     * EXPERIMENTAL, FAR TO BE SATISFAYING
     */
    public function getVisitDicoms(int $visitId = 0, GetDicoms $getDicoms, GetDicomsRequest $getDicomsRequest, GetDicomsResponse $getDicomsResponse){

        return response()->streamDownload(function() use( &$getDicoms, &$getDicomsRequest, &$getDicomsResponse){
            $getDicoms->execute($getDicomsRequest, $getDicomsResponse);
        }, 'downloadDicomGaelO.zip');
    }
}
