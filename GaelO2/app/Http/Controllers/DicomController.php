<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\DeleteSeries\DeleteSeries;
use App\GaelO\UseCases\DeleteSeries\DeleteSeriesRequest;
use App\GaelO\UseCases\DeleteSeries\DeleteSeriesResponse;
use App\GaelO\UseCases\GetDicoms\GetDicoms;
use App\GaelO\UseCases\GetDicoms\GetDicomsRequest;
use App\GaelO\UseCases\GetDicoms\GetDicomsResponse;
use App\GaelO\UseCases\GetDicomSeriesMetadata\GetDicomSeriesMetadata;
use App\GaelO\UseCases\GetDicomSeriesMetadata\GetDicomSeriesMetadataRequest;
use App\GaelO\UseCases\GetDicomSeriesMetadata\GetDicomSeriesMetadataResponse;
use App\GaelO\UseCases\GetDicomSeriesPreview\GetDicomSeriesPreview;
use App\GaelO\UseCases\GetDicomSeriesPreview\GetDicomSeriesPreviewRequest;
use App\GaelO\UseCases\GetDicomSeriesPreview\GetDicomSeriesPreviewResponse;
use App\GaelO\UseCases\GetDicomsFile\GetDicomsFile;
use App\GaelO\UseCases\GetDicomsFile\GetDicomsFileRequest;
use App\GaelO\UseCases\GetDicomsFile\GetDicomsFileResponse;
use App\GaelO\UseCases\GetDicomsFileSupervisor\GetDicomsFileSupervisor;
use App\GaelO\UseCases\GetDicomsFileSupervisor\GetDicomsFileSupervisorRequest;
use App\GaelO\UseCases\GetDicomsFileSupervisor\GetDicomsFileSupervisorResponse;
use App\GaelO\UseCases\GetDicomStudyMetadata\GetDicomStudyMetadata;
use App\GaelO\UseCases\GetDicomStudyMetadata\GetDicomStudyMetadataRequest;
use App\GaelO\UseCases\GetDicomStudyMetadata\GetDicomStudyMetadataResponse;
use App\GaelO\UseCases\GetNiftiFileSupervisor\GetNiftiFileSupervisor;
use App\GaelO\UseCases\GetNiftiFileSupervisor\GetNiftiFileSupervisorRequest;
use App\GaelO\UseCases\GetNiftiFileSupervisor\GetNiftiFileSupervisorResponse;
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
    public function getVisitDicomsFile(Request $request, GetDicomsFile $getDicomsFile, GetDicomsFileRequest $getDicomsFileRequest, GetDicomsFileResponse $getDicomsFileResponse, int $visitId)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getDicomsFileRequest->currentUserId = $currentUser['id'];
        $getDicomsFileRequest->visitId = $visitId;
        $getDicomsFileRequest->studyName = $queryParam['studyName'];
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

    public function getVisitDicoms(Request $request, GetDicoms $getDicoms, GetDicomsRequest $getDicomsRequest, GetDicomsResponse $getDicomsResponse, int $visitId)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getDicomsRequest->currentUserId = $currentUser['id'];
        $getDicomsRequest->studyName = $queryParam['studyName'];
        $getDicomsRequest->visitId = $visitId;
        $getDicomsRequest->role = $queryParam['role'];
        $getDicoms->execute($getDicomsRequest, $getDicomsResponse);

        return $this->getJsonResponse($getDicomsResponse->body, $getDicomsResponse->status, $getDicomsResponse->statusText);
    }

    public function deleteSeries(Request $request, DeleteSeries $deleteSeries, DeleteSeriesRequest $deleteSeriesRequest, DeleteSeriesResponse $deleteSeriesResponse, string $seriesInstanceUID)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $requestData = $request->all();

        Util::fillObject($requestData, $deleteSeriesRequest);
        $deleteSeriesRequest->seriesInstanceUID = $seriesInstanceUID;
        $deleteSeriesRequest->role = $queryParam['role'];
        $deleteSeriesRequest->studyName = $queryParam['studyName'];
        $deleteSeriesRequest->currentUserId = $currentUser['id'];

        $deleteSeries->execute($deleteSeriesRequest, $deleteSeriesResponse);

        return $this->getJsonResponse($deleteSeriesResponse->body, $deleteSeriesResponse->status, $deleteSeriesResponse->statusText);
    }

    public function reactivateSeries(Request $request, ReactivateDicomSeries $reactivateDicomSeries, ReactivateDicomSeriesRequest $reactivateDicomSeriesRequest, ReactivateDicomSeriesResponse $reactivateDicomSeriesResponse, string $seriesInstanceUID)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();
        $queryParam = $request->query();

        Util::fillObject($requestData, $reactivateDicomSeriesRequest);
        $reactivateDicomSeriesRequest->studyName = $queryParam['studyName'];
        $reactivateDicomSeriesRequest->seriesInstanceUID = $seriesInstanceUID;
        $reactivateDicomSeriesRequest->currentUserId = $currentUser['id'];
        $reactivateDicomSeriesRequest->role = $queryParam['role'];

        $reactivateDicomSeries->execute($reactivateDicomSeriesRequest, $reactivateDicomSeriesResponse);

        return $this->getJsonResponse($reactivateDicomSeriesResponse->body, $reactivateDicomSeriesResponse->status, $reactivateDicomSeriesResponse->statusText);
    }

    public function reactivateStudy(Request $request, ReactivateDicomStudy $reactivateDicomStudy, ReactivateDicomStudyRequest $reactivateDicomStudyRequest, ReactivateDicomStudyResponse $reactivateDicomStudyResponse, string $studyInstanceUID)
    {

        $currentUser = Auth::user();
        $requestData = $request->all();
        $queryParam = $request->query();

        Util::fillObject($requestData, $reactivateDicomStudyRequest);
        $reactivateDicomStudyRequest->studyName = $queryParam['studyName'];
        $reactivateDicomStudyRequest->studyInstanceUID = $studyInstanceUID;
        $reactivateDicomStudyRequest->currentUserId = $currentUser['id'];

        $reactivateDicomStudy->execute($reactivateDicomStudyRequest, $reactivateDicomStudyResponse);

        return $this->getJsonResponse($reactivateDicomStudyResponse->body, $reactivateDicomStudyResponse->status, $reactivateDicomStudyResponse->statusText);
    }

    public function getSupervisorDicomsFile(Request $request, GetDicomsFileSupervisor $getDicomsFileSupervisor, GetDicomsFileSupervisorRequest $getDicomsFileSupervisorRequest, GetDicomsFileSupervisorResponse $getDicomsFileSupervisorResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        Util::fillObject($requestData, $getDicomsFileSupervisorRequest);
        $getDicomsFileSupervisorRequest->currentUserId = $currentUser['id'];
        $getDicomsFileSupervisorRequest->studyName = $studyName;

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

    public function getNiftiSeries(Request $request, GetNiftiFileSupervisor $getNiftiFileSupervisor, GetNiftiFileSupervisorRequest $getNiftiFileSupervisorRequest, GetNiftiFileSupervisorResponse $getNiftiFileSupervisorResponse, string $seriesInstanceUID)
    {

        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getNiftiFileSupervisorRequest->currentUserId = $currentUser['id'];
        $getNiftiFileSupervisorRequest->studyName = $queryParam['studyName'];
        $getNiftiFileSupervisorRequest->seriesInstanceUID = $seriesInstanceUID;
        $getNiftiFileSupervisorRequest->compress = array_key_exists('compress', $queryParam);
        $getNiftiFileSupervisor->execute($getNiftiFileSupervisorRequest, $getNiftiFileSupervisorResponse);

        if ($getNiftiFileSupervisorResponse->status === 200) {
            return response()->streamDownload(function () use (&$getNiftiFileSupervisor) {
                $getNiftiFileSupervisor->outputStream();
            }, $getNiftiFileSupervisorResponse->filename);
        } else {
            return response()->json($getNiftiFileSupervisorResponse->body)
                ->setStatusCode($getNiftiFileSupervisorResponse->status, $getNiftiFileSupervisorResponse->statusText);
        }
    }

    public function getStudyMetadata(Request $request, GetDicomStudyMetadata $getDicomStudyMetadata, GetDicomStudyMetadataRequest $getDicomStudyMetadataRequest, GetDicomStudyMetadataResponse $getDicomStudyMetadataResponse, string $studyInstanceUID)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getDicomStudyMetadataRequest->studyInstanceUID = $studyInstanceUID;
        $getDicomStudyMetadataRequest->role = $queryParam['role'];
        $getDicomStudyMetadataRequest->currentUserId = $currentUser['id'];

        $getDicomStudyMetadata->execute($getDicomStudyMetadataRequest, $getDicomStudyMetadataResponse);

        return $this->getJsonResponse($getDicomStudyMetadataResponse->body, $getDicomStudyMetadataResponse->status, $getDicomStudyMetadataResponse->statusText);
    }

    public function getSeriesMetadata(Request $request, GetDicomSeriesMetadata $getDicomSeriesMetadata, GetDicomSeriesMetadataRequest $getDicomSeriesMetadataRequest, GetDicomSeriesMetadataResponse $getDicomSeriesMetadataResponse, string $seriesInstanceUID)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getDicomSeriesMetadataRequest->seriesInstanceUID = $seriesInstanceUID;
        $getDicomSeriesMetadataRequest->role = $queryParam['role'];
        $getDicomSeriesMetadataRequest->currentUserId = $currentUser['id'];

        $getDicomSeriesMetadata->execute($getDicomSeriesMetadataRequest, $getDicomSeriesMetadataResponse);

        return $this->getJsonResponse($getDicomSeriesMetadataResponse->body, $getDicomSeriesMetadataResponse->status, $getDicomSeriesMetadataResponse->statusText);
    }

    public function getSeriesPreview(Request $request, GetDicomSeriesPreview $getDicomSeriesPreview, GetDicomSeriesPreviewRequest $getDicomSeriesPreviewRequest, GetDicomSeriesPreviewResponse $getDicomSeriesPreviewResponse, string $seriesInstanceUID, int $index)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getDicomSeriesPreviewRequest->role = $queryParam['role'];
        $getDicomSeriesPreviewRequest->seriesInstanceUID = $seriesInstanceUID;
        $getDicomSeriesPreviewRequest->index = $index;
        $getDicomSeriesPreviewRequest->currentUserId = $currentUser['id'];

        $getDicomSeriesPreview->execute($getDicomSeriesPreviewRequest, $getDicomSeriesPreviewResponse);

        return response($getDicomSeriesPreviewResponse->body, $getDicomSeriesPreviewResponse->status)->header('Content-Type', $getDicomSeriesPreviewResponse->contentType);
    }
}
