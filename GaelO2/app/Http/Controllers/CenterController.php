<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateCenter\CreateCenter;
use App\GaelO\UseCases\CreateCenter\CreateCenterRequest;
use App\GaelO\UseCases\CreateCenter\CreateCenterResponse;
use App\GaelO\UseCases\DeleteCenter\DeleteCenter;
use App\GaelO\UseCases\DeleteCenter\DeleteCenterRequest;
use App\GaelO\UseCases\DeleteCenter\DeleteCenterResponse;
use App\GaelO\UseCases\ModifyCenter\ModifyCenter;
use App\GaelO\UseCases\ModifyCenter\ModifyCenterRequest;
use App\GaelO\UseCases\ModifyCenter\ModifyCenterResponse;
use App\GaelO\UseCases\GetCenter\GetCenter;
use App\GaelO\UseCases\GetCenter\GetCenterRequest;
use App\GaelO\UseCases\GetCenter\GetCenterResponse;
use App\GaelO\UseCases\GetCentersFromStudy\GetCentersFromStudy;
use App\GaelO\UseCases\GetCentersFromStudy\GetCentersFromStudyRequest;
use App\GaelO\UseCases\GetCentersFromStudy\GetCentersFromStudyResponse;

use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CenterController extends Controller
{
    public function getCenter(Request $request, GetCenterRequest $getCenterRequest, GetCenterResponse $getCenterResponse, GetCenter $getCenter, ?int $code = null)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();
        Util::fillObject($requestData, $getCenterRequest);
        $getCenterRequest->currentUserId = $currentUser['id'];
        $getCenterRequest->code = $code;
        $getCenter->execute($getCenterRequest, $getCenterResponse);
        return $this->getJsonResponse($getCenterResponse->body, $getCenterResponse->status, $getCenterResponse->statusText);
    }

    public function modifyCenter(Request $request, ModifyCenterRequest $modifyCenterRequest, ModifyCenterResponse $modifyCenterResponse, ModifyCenter $modifyCenter, int $code)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        Util::fillObject($requestData, $modifyCenterRequest);
        $modifyCenterRequest->currentUserId = $currentUser['id'];
        $modifyCenterRequest->code = $code;

        $modifyCenter->execute($modifyCenterRequest, $modifyCenterResponse);
        return $this->getJsonResponse($modifyCenterResponse->body, $modifyCenterResponse->status, $modifyCenterResponse->statusText);
    }

    public function createCenter(Request $request, CreateCenter $createCenter, CreateCenterRequest $createCenterRequest, CreateCenterResponse $createCenterResponse)
    {

        $currentUser = Auth::user();
        $createCenterRequest->currentUserId = $currentUser['id'];

        $requestData = $request->all();
        Util::fillObject($requestData, $createCenterRequest);

        $createCenter->execute($createCenterRequest, $createCenterResponse);
        return $this->getJsonResponse($createCenterResponse->body, $createCenterResponse->status, $createCenterResponse->statusText);
    }

    public function getCentersFromStudy(
        GetCentersFromStudy $getCentersFromStudy,
        GetCentersFromStudyRequest $getCentersFromStudyRequest,
        GetCentersFromStudyResponse $getCentersFromStudyResponse,
        String $studyName
    ) {

        $currentUser = Auth::user();
        $getCentersFromStudyRequest->currentUserId = $currentUser['id'];
        $getCentersFromStudyRequest->studyName = $studyName;

        $getCentersFromStudy->execute($getCentersFromStudyRequest, $getCentersFromStudyResponse);
        return $this->getJsonResponse($getCentersFromStudyResponse->body, $getCentersFromStudyResponse->status, $getCentersFromStudyResponse->statusText);
    }

    public function deleteCenter(DeleteCenter $deleteCenter, DeleteCenterRequest $deleteCenterRequest, DeleteCenterResponse $deleteCenterResponse, int $centerCode)
    {
        $currentUser = Auth::user();
        $deleteCenterRequest->currentUserId = $currentUser['id'];
        $deleteCenterRequest->centerCode = $centerCode;

        $deleteCenter->execute($deleteCenterRequest, $deleteCenterResponse);
        return $this->getJsonResponse($deleteCenterResponse->body, $deleteCenterResponse->status, $deleteCenterResponse->statusText);
    }
}
