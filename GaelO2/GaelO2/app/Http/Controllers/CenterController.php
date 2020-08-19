<?php

namespace App\Http\Controllers;

use App;
use App\GaelO\UseCases\ModifyCenter\ModifyCenterRequest;
use App\GaelO\UseCases\ModifyCenter\ModifyCenterResponse;
use Illuminate\Http\Request;
use App\GaelO\UseCases\GetCenter\GetCenterRequest;
use App\GaelO\UseCases\GetCenter\GetCenterResponse;
use App\GaelO\Util;

class CenterController extends Controller
{
    public function getCenter(int $code=0, GetCenterRequest $getCenterRequest, GetCenterResponse $getCenterResponse) {
        $getCenterRequest->code = $code;
        $getCenter = App::make('GetCenter');
        $getCenter->execute($getCenterRequest, $getCenterResponse);
        return response()->json($getCenterResponse->body, $getCenterResponse->status);
    }

    public function modifyCenter(Request $request, ModifyCenterRequest $modifyCenterRequest, ModifyCenterResponse $modifyCenterResponse) {
        $requestData = $request->all();
        $modifyCenterRequest = Util::fillObject($requestData, $modifyCenterRequest);
        $modifyCenter = App::make('ModifyCenter');
        $modifyCenter->execute($modifyCenterRequest, $modifyCenterResponse);
        return response()->json($modifyCenterResponse->body, $modifyCenterResponse->status);
    }
}
