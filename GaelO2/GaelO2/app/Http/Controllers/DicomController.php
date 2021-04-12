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
use App\GaelO\UseCases\GetDicomsFileSupervisor\GetDicomsFileSupervisor;
use App\GaelO\UseCases\GetDicomsFileSupervisor\GetDicomsFileSupervisorRequest;
use App\GaelO\UseCases\GetDicomsFileSupervisor\GetDicomsFileSupervisorResponse;
use App\GaelO\UseCases\GetDicomsStudy\GetDicomsStudy;
use App\GaelO\UseCases\GetDicomsStudy\GetDicomsStudyRequest;
use App\GaelO\UseCases\GetDicomsStudy\GetDicomsStudyResponse;
use App\GaelO\UseCases\ReactivateDicomSeries\ReactivateDicomSeries;
use App\GaelO\UseCases\ReactivateDicomSeries\ReactivateDicomSeriesRequest;
use App\GaelO\UseCases\ReactivateDicomSeries\ReactivateDicomSeriesResponse;
use App\GaelO\UseCases\ReactivateDicomStudy\ReactivateDicomStudy;
use App\GaelO\UseCases\ReactivateDicomStudy\ReactivateDicomStudyRequest;
use App\GaelO\UseCases\ReactivateDicomStudy\ReactivateDicomStudyResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DicomController extends Controller
{


    public function getVisitDicomsFile(int $visitId, Request $request, GetDicomsFile $getDicomsFile, GetDicomsFileRequest $getDicomsFileRequest, GetDicomsFileResponse $getDicomsFileResponse){
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getDicomsFileRequest->currentUserId = $currentUser['id'];
        $getDicomsFileRequest->visitId = $visitId;
        $getDicomsFileRequest->role = $queryParam['role'];
        $getDicomsFile->execute($getDicomsFileRequest, $getDicomsFileResponse);

        if($getDicomsFileResponse->status === 200) {
            return response()->streamDownload( function() use( &$getDicomsFile){
                $getDicomsFile->outputStream();
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

    public function reactivateStudy(string $studyInstanceUID, ReactivateDicomStudy $reactivateDicomStudy, ReactivateDicomStudyRequest $reactivateDicomStudyRequest, ReactivateDicomStudyResponse $reactivateDicomStudyResponse){

        $currentUser = Auth::user();

        $reactivateDicomStudyRequest->studyInstanceUID = $studyInstanceUID;
        $reactivateDicomStudyRequest->currentUserId = $currentUser['id'];

        $reactivateDicomStudy->execute($reactivateDicomStudyRequest, $reactivateDicomStudyResponse);

        if($reactivateDicomStudyResponse->body === null){
            return response()->noContent()
            ->setStatusCode($reactivateDicomStudyResponse->status, $reactivateDicomStudyResponse->statusText);
        }else{
            return response()->json($reactivateDicomStudyResponse->body)
            ->setStatusCode($reactivateDicomStudyResponse->status, $reactivateDicomStudyResponse->statusText);
        }


    }

    public function getStudyDicomStudies(string $studyName, Request $request, GetDicomsStudy $getDicomsStudy, GetDicomsStudyRequest $getDicomsStudyRequest, GetDicomsStudyResponse $getDicomsStudyResponse){
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getDicomsStudyRequest->studyName = $studyName;
        $getDicomsStudyRequest->withTrashed = key_exists('withTrashed', $queryParam);
        $getDicomsStudyRequest->currentUserId = $currentUser['id'];

        $getDicomsStudy->execute($getDicomsStudyRequest, $getDicomsStudyResponse);

        if($getDicomsStudyResponse->body === null){
            return response()->noContent()
            ->setStatusCode($getDicomsStudyResponse->status, $getDicomsStudyResponse->statusText);
        }else{
            return response()->json($getDicomsStudyResponse->body)
            ->setStatusCode($getDicomsStudyResponse->status, $getDicomsStudyResponse->statusText);
        }


    }

    public function getSupervisorDicomsFile(string $studyName, Request $request, GetDicomsFileSupervisor $getDicomsFileSupervisor, GetDicomsFileSupervisorRequest $getDicomsFileSupervisorRequest, GetDicomsFileSupervisorResponse $getDicomsFileSupervisorResponse){
        $currentUser = Auth::user();
        $requestData = $request->all();

        $getDicomsFileSupervisorRequest->currentUserId = $currentUser['id'];
        $getDicomsFileSupervisorRequest->studyName = $studyName;
        $getDicomsFileSupervisorRequest = Util::fillObject($requestData, $getDicomsFileSupervisorRequest);

        $getDicomsFileSupervisor->execute($getDicomsFileSupervisorRequest, $getDicomsFileSupervisorResponse);

        if($getDicomsFileSupervisorResponse->status === 200) {
            return response()->streamDownload( function() use( &$getDicomsFileSupervisor){
                $getDicomsFileSupervisor->outputStream();
            }, $getDicomsFileSupervisorResponse->filename);
        }else{
            return response()->json($getDicomsFileSupervisorResponse->body)
            ->setStatusCode($getDicomsFileSupervisorResponse->status, $getDicomsFileSupervisorResponse->statusText);
        }

    }
}
