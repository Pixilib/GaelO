<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetPatientsInStudyFromCenter\GetPatientsInStudyFromCenter;
use App\GaelO\UseCases\GetPatientsInStudyFromCenter\GetPatientsInStudyFromCenterRequest;
use App\GaelO\UseCases\GetPatientsInStudyFromCenter\GetPatientsInStudyFromCenterResponse;
use App\GaelO\UseCases\GetCentersFromStudy\GetCentersFromStudy;
use App\GaelO\UseCases\GetCentersFromStudy\GetCentersFromStudyRequest;
use App\GaelO\UseCases\GetCentersFromStudy\GetCentersFromStudyResponse;

use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Log;

class ToolsController extends Controller
{

    public function getCentersFromStudy(String $studyName,
        GetCentersFromStudy $getCentersFromStudy,
        GetCentersFromStudyRequest $getCentersFromStudyRequest, 
        GetCentersFromStudyResponse $getCentersFromStudyResponse) {
            
        $curentUser = Auth::user();
        $getCentersFromStudyRequest->currentUserId = $curentUser['id'];
        $getCentersFromStudyRequest->studyName = $studyName;

        $getCentersFromStudy->execute($getCentersFromStudyRequest, $getCentersFromStudyResponse);
        return $this->getJsonResponse($getCentersFromStudyResponse->body, $getCentersFromStudyResponse->status, $getCentersFromStudyResponse->statusText);
    } 

    public function getPatientsInStudyFromCenter(String $studyName, int $centerCode,
        GetPatientsInStudyFromCenter $getPatientsInStudyFromCenter,
        GetPatientsInStudyFromCenterRequest $getPatientsInStudyFromCenterRequest, 
        GetPatientsInStudyFromCenterResponse $getPatientsInStudyFromCenterResponse) {

        $curentUser = Auth::user();
        $getPatientsInStudyFromCenterRequest->currentUserId = $curentUser['id'];
        $getPatientsInStudyFromCenterRequest->studyName = $studyName;
        $getPatientsInStudyFromCenterRequest->centerCode = $centerCode;
        

        $getPatientsInStudyFromCenter->execute($getPatientsInStudyFromCenterRequest, $getPatientsInStudyFromCenterResponse);
        return $this->getJsonResponse($getPatientsInStudyFromCenterResponse->body, $getPatientsInStudyFromCenterResponse->status, $getPatientsInStudyFromCenterResponse->statusText);
    }
}
