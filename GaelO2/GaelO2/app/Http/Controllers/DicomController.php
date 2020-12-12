<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\DeleteSeries\DeleteSeries;
use App\GaelO\UseCases\DeleteSeries\DeleteSeriesRequest;
use App\GaelO\UseCases\DeleteSeries\DeleteSeriesResponse;
use App\GaelO\UseCases\GetDicomsFile\GetDicomsFile;
use App\GaelO\UseCases\GetDicomsFile\GetDicomsFileRequest;
use App\GaelO\UseCases\GetDicomsFile\GetDicomsFileResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DicomController extends Controller
{


    public function getVisitDicomsFile(int $visitId = 0, Request $request, GetDicomsFile $getDicoms, GetDicomsFileRequest $getDicomsRequest, GetDicomsFileResponse $getDicomsResponse){
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getDicomsRequest->currentUserId = $currentUser['id'];
        $getDicomsRequest->visitId = $visitId;
        $getDicomsRequest->role = $queryParam['role'];
        $getDicoms->execute($getDicomsRequest, $getDicomsResponse);

        if($getDicomsResponse->status === 200) {
            return response()->streamDownload( function() use( &$getDicoms){
                $getDicoms->outputStream();
            }, $getDicomsResponse->filename);
        }else{
            return response()->json($getDicomsResponse->body)
            ->setStatusCode($getDicomsResponse->status, $getDicomsResponse->statusText);
        }


    }

    public function deleteSeries(string $seriesInstanceUID, Request $request, DeleteSeries $deleteSeries, DeleteSeriesRequest $deleteSeriesRequest, DeleteSeriesResponse $deleteSeriesResponse){
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $requestData = $request->all();

        $deleteSeriesRequest->seriesInstanceUID = $seriesInstanceUID;
        $deleteSeriesRequest->role = $queryParam['role'];
        $deleteSeriesRequest->currentUserId = $currentUser['id'];
        $deleteSeriesRequest = Util::fillObject($requestData, $deleteSeriesRequest);


        $deleteSeries->execute($deleteSeriesRequest, $deleteSeriesResponse);

        if($deleteSeriesResponse->body === null){
            return response()->noContent()
            ->setStatusCode($deleteSeriesResponse->status, $deleteSeriesResponse->statusText);
        }else{
            return response()->json($deleteSeriesResponse->body)
            ->setStatusCode($deleteSeriesResponse->status, $deleteSeriesResponse->statusText);
        }
    }
}
