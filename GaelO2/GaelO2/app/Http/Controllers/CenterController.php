<?php

namespace App\Http\Controllers;

use App;
use App\GaelO\UseCases\GetCenter\GetCenter;
use App\GaelO\UseCases\ModifyCenter\ModifyCenterRequest;
use App\GaelO\UseCases\ModifyCenter\ModifyCenterResponse;
use Illuminate\Http\Request;
use App\GaelO\UseCases\GetCenter\GetCenterRequest;
use App\GaelO\UseCases\GetCenter\GetCenterResponse;
use App\GaelO\UseCases\ModifyCenter\ModifyCenter;
use App\GaelO\Util;

class CenterController extends Controller
{
    public function getCenter(int $code=0, GetCenterRequest $getCenterRequest, GetCenterResponse $getCenterResponse, GetCenter $getCenter) {
        $getCenterRequest->code = $code;
        $getCenter->execute($getCenterRequest, $getCenterResponse);
        return response()->json($getCenterResponse->body)
                ->setStatusCode($getCenterResponse->status, $getCenterResponse->statusText);
    }

    public function modifyCenter(Request $request, ModifyCenterRequest $modifyCenterRequest, ModifyCenterResponse $modifyCenterResponse, ModifyCenter $modifyCenter) {
        $requestData = $request->all();
        $modifyCenterRequest = Util::fillObject($requestData, $modifyCenterRequest);
        $modifyCenter->execute($modifyCenterRequest, $modifyCenterResponse);
        return response()->json($modifyCenterResponse->body)
                ->setStatusCode($modifyCenterResponse->status, $modifyCenterResponse->statusText);
    }
}
