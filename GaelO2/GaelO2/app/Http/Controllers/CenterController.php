<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateCenter\CreateCenter;
use App\GaelO\UseCases\CreateCenter\CreateCenterRequest;
use App\GaelO\UseCases\CreateCenter\CreateCenterResponse;
use App\GaelO\UseCases\GetCenter\GetCenter;
use App\GaelO\UseCases\ModifyCenter\ModifyCenterRequest;
use App\GaelO\UseCases\ModifyCenter\ModifyCenterResponse;
use Illuminate\Http\Request;
use App\GaelO\UseCases\GetCenter\GetCenterRequest;
use App\GaelO\UseCases\GetCenter\GetCenterResponse;
use App\GaelO\UseCases\ModifyCenter\ModifyCenter;
use App\GaelO\Util;
use Illuminate\Support\Facades\Auth;

class CenterController extends Controller
{
    public function getCenter(int $code=null, GetCenterRequest $getCenterRequest, GetCenterResponse $getCenterResponse, GetCenter $getCenter) {
        $currentUser = Auth::user();
        $getCenterRequest->currentUserId = $currentUser['id'];
        $getCenterRequest->code = $code;
        $getCenter->execute($getCenterRequest, $getCenterResponse);
        return $this->getJsonResponse($getCenterResponse->body, $getCenterResponse->status, $getCenterResponse->statusText);
    }

    public function modifyCenter(int $code, Request $request, ModifyCenterRequest $modifyCenterRequest, ModifyCenterResponse $modifyCenterResponse, ModifyCenter $modifyCenter) {
        $currentUser = Auth::user();
        $modifyCenterRequest->currentUserId = $currentUser['id'];
        $modifyCenterRequest->code = $code;

        $requestData = $request->all();
        $modifyCenterRequest = Util::fillObject($requestData, $modifyCenterRequest);
        $modifyCenter->execute($modifyCenterRequest, $modifyCenterResponse);
        return $this->getJsonResponse($modifyCenterResponse->body, $modifyCenterResponse->status, $modifyCenterResponse->statusText);
    }

    public function createCenter(Request $request, CreateCenter $createCenter, CreateCenterRequest $createCenterRequest, CreateCenterResponse $createCenterResponse){

        $currentUser = Auth::user();
        $createCenterRequest->currentUserId = $currentUser['id'];

        $requestData = $request->all();
        $createCenterRequest = Util::fillObject($requestData, $createCenterRequest);

        $createCenter->execute($createCenterRequest, $createCenterResponse);
        return $this->getJsonResponse($createCenterResponse->body, $createCenterResponse->status, $createCenterResponse->statusText);
    }
}
