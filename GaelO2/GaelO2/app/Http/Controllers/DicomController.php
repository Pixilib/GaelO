<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\DeleteSeries\DeleteSeries;
use App\GaelO\UseCases\DeleteSeries\DeleteSeriesRequest;
use App\GaelO\UseCases\DeleteSeries\DeleteSeriesResponse;
use App\GaelO\UseCases\GetDicoms\GetDicoms;
use App\GaelO\UseCases\GetDicoms\GetDicomsRequest;
use App\GaelO\UseCases\GetDicoms\GetDicomsResponse;
use App\GaelO\UseCases\GetDicomsFile\GetDicomsFile;
use App\GaelO\UseCases\GetDicomsFile\GetDicomsFileRequest;
use App\GaelO\UseCases\GetDicomsFile\GetDicomsFileResponse;
use App\GaelO\UseCases\ReactivateDicomSeries\ReactivateDicomSeries;
use App\GaelO\UseCases\ReactivateDicomSeries\ReactivateDicomSeriesRequest;
use App\GaelO\UseCases\ReactivateDicomSeries\ReactivateDicomSeriesResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DicomController extends Controller
{


    public function getVisitDicomsFile(int $visitId = 0, Request $request, GetDicomsFile $getDicomsFile, GetDicomsFileRequest $getDicomsFileRequest, GetDicomsFileResponse $getDicomsFileResponse){
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getDicomsFileRequest->currentUserId = $currentUser['id'];
        $getDicomsFileRequest->visitId = $visitId;
        $getDicomsFileRequest->role = $queryParam['role'];
        $getDicomsFile->execute($getDicomsFileRequest, $getDicomsFileResponse);

        if($getDicomsFileResponse->status === 200) {
            return response()->streamDownload( function() use( &$getDicoms){
                $getDicoms->outputStream();
            }, $getDicomsFileResponse->filename);
        }else{
            return response()->json($getDicomsFileResponse->body)
            ->setStatusCode($getDicomsFileResponse->status, $getDicomsFileResponse->statusText);
        }


    }

    public function getVisitDicoms(int $visitId, Request $request, GetDicoms $getDicoms, GetDicomsRequest $getDicomsRequest, GetDicomsResponse $getDicomsResponse){
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getDicomsRequest->currentUserId = $currentUser['id'];
        $getDicomsRequest->visitId = $visitId;
        $getDicomsRequest->role = $queryParam['role'];
        $getDicoms->execute($getDicomsRequest, $getDicomsResponse);

        return response()->json($getDicomsResponse->body)
        ->setStatusCode($getDicomsResponse->status, $getDicomsResponse->statusText);

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

    public function reactivateSeries(string $seriesInstanceUID, ReactivateDicomSeries $reactivateDicomSeries, ReactivateDicomSeriesRequest $reactivateDicomSeriesRequest, ReactivateDicomSeriesResponse $reactivateDicomSeriesResponse){
        $currentUser = Auth::user();

        $reactivateDicomSeriesRequest->seriesInstanceUID = $seriesInstanceUID;
        $reactivateDicomSeriesRequest->currentUserId = $currentUser['id'];

        $reactivateDicomSeries->execute($reactivateDicomSeriesRequest, $reactivateDicomSeriesResponse);

        if($reactivateDicomSeriesResponse->body === null){
            return response()->noContent()
            ->setStatusCode($reactivateDicomSeriesResponse->status, $reactivateDicomSeriesResponse->statusText);
        }else{
            return response()->json($reactivateDicomSeriesResponse->body)
            ->setStatusCode($reactivateDicomSeriesResponse->status, $reactivateDicomSeriesResponse->statusText);
        }
    }
}
