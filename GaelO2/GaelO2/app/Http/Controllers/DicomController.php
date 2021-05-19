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
    public function getVisitDicomsFile(int $visitId, Request $request, GetDicomsFile $getDicomsFile, GetDicomsFileRequest $getDicomsFileRequest, GetDicomsFileResponse $getDicomsFileResponse)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getDicomsFileRequest->currentUserId = $currentUser['id'];
        $getDicomsFileRequest->visitId = $visitId;
        $getDicomsFileRequest->role = $queryParam['role'];
        $getDicomsFile->execute($getDicomsFileRequest, $getDicomsFileResponse);

        if ($getDicomsFileResponse->status === 200) {
            return response()->streamDownload(function () use (&$getDicomsFile) {
                $getDicomsFile->outputStream();
            }, $getDicomsFileResponse->filename);
        } else {
            return response()->json($getDicomsFileResponse->body)
                ->setStatusCode($getDicomsFileResponse->status, $getDicomsFileResponse->statusText);
        }
    }

    public function getVisitDicoms(int $visitId, Request $request, GetDicoms $getDicoms, GetDicomsRequest $getDicomsRequest, GetDicomsResponse $getDicomsResponse)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getDicomsRequest->currentUserId = $currentUser['id'];
        $getDicomsRequest->visitId = $visitId;
        $getDicomsRequest->role = $queryParam['role'];
        $getDicoms->execute($getDicomsRequest, $getDicomsResponse);

        return $this->getJsonResponse($getDicomsResponse->body, $getDicomsResponse->status, $getDicomsResponse->statusText);
    }

    public function deleteSeries(string $seriesInstanceUID, Request $request, DeleteSeries $deleteSeries, DeleteSeriesRequest $deleteSeriesRequest, DeleteSeriesResponse $deleteSeriesResponse)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $requestData = $request->all();

        $deleteSeriesRequest->seriesInstanceUID = $seriesInstanceUID;
        $deleteSeriesRequest->role = $queryParam['role'];
        $deleteSeriesRequest->currentUserId = $currentUser['id'];
        $deleteSeriesRequest = Util::fillObject($requestData, $deleteSeriesRequest);

        $deleteSeries->execute($deleteSeriesRequest, $deleteSeriesResponse);

        return $this->getJsonResponse($deleteSeriesResponse->body, $deleteSeriesResponse->status, $deleteSeriesResponse->statusText);
    }

    public function reactivateSeries(string $seriesInstanceUID, Request $request, ReactivateDicomSeries $reactivateDicomSeries, ReactivateDicomSeriesRequest $reactivateDicomSeriesRequest, ReactivateDicomSeriesResponse $reactivateDicomSeriesResponse)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        $reactivateDicomSeriesRequest->seriesInstanceUID = $seriesInstanceUID;
        $reactivateDicomSeriesRequest->currentUserId = $currentUser['id'];
        $reactivateDicomSeriesRequest = Util::fillObject($requestData, $reactivateDicomSeriesRequest);

        $reactivateDicomSeries->execute($reactivateDicomSeriesRequest, $reactivateDicomSeriesResponse);

        return $this->getJsonResponse($reactivateDicomSeriesResponse->body, $reactivateDicomSeriesResponse->status, $reactivateDicomSeriesResponse->statusText);
    }

    public function reactivateStudy(string $studyInstanceUID, Request $request, ReactivateDicomStudy $reactivateDicomStudy, ReactivateDicomStudyRequest $reactivateDicomStudyRequest, ReactivateDicomStudyResponse $reactivateDicomStudyResponse)
    {

        $currentUser = Auth::user();
        $requestData = $request->all();

        $reactivateDicomStudyRequest->studyInstanceUID = $studyInstanceUID;
        $reactivateDicomStudyRequest->currentUserId = $currentUser['id'];
        $reactivateDicomStudyRequest = Util::fillObject($requestData, $reactivateDicomStudyRequest);

        $reactivateDicomStudy->execute($reactivateDicomStudyRequest, $reactivateDicomStudyResponse);

        return $this->getJsonResponse($reactivateDicomStudyResponse->body, $reactivateDicomStudyResponse->status, $reactivateDicomStudyResponse->statusText);
    }

    public function getStudyDicomStudies(string $studyName, Request $request, GetDicomsStudy $getDicomsStudy, GetDicomsStudyRequest $getDicomsStudyRequest, GetDicomsStudyResponse $getDicomsStudyResponse)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getDicomsStudyRequest->studyName = $studyName;
        $getDicomsStudyRequest->withTrashed = key_exists('withTrashed', $queryParam);
        $getDicomsStudyRequest->currentUserId = $currentUser['id'];

        $getDicomsStudy->execute($getDicomsStudyRequest, $getDicomsStudyResponse);

        return $this->getJsonResponse($getDicomsStudyResponse->body, $getDicomsStudyResponse->status, $getDicomsStudyResponse->statusText);
    }

    public function getSupervisorDicomsFile(string $studyName, Request $request, GetDicomsFileSupervisor $getDicomsFileSupervisor, GetDicomsFileSupervisorRequest $getDicomsFileSupervisorRequest, GetDicomsFileSupervisorResponse $getDicomsFileSupervisorResponse)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        $getDicomsFileSupervisorRequest->currentUserId = $currentUser['id'];
        $getDicomsFileSupervisorRequest->studyName = $studyName;
        $getDicomsFileSupervisorRequest = Util::fillObject($requestData, $getDicomsFileSupervisorRequest);

        $getDicomsFileSupervisor->execute($getDicomsFileSupervisorRequest, $getDicomsFileSupervisorResponse);

        if ($getDicomsFileSupervisorResponse->status === 200) {
            return response()->streamDownload(function () use (&$getDicomsFileSupervisor) {
                $getDicomsFileSupervisor->outputStream();
            }, $getDicomsFileSupervisorResponse->filename);
        } else {
            return response()->json($getDicomsFileSupervisorResponse->body)
                ->setStatusCode($getDicomsFileSupervisorResponse->status, $getDicomsFileSupervisorResponse->statusText);
        }
    }
}
