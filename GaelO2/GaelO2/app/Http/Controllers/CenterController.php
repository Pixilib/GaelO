<?php

namespace App\Http\Controllers;

use App;
use Illuminate\Http\Request;
use App\GaelO\UseCases\GetCenter\GetCenter;
use App\GaelO\UseCases\GetCenter\GetCenterRequest;
use App\GaelO\UseCases\GetCenter\GetCenterResponse;

class CenterController extends Controller
{
    public function getCenter(int $code=0, GetCenterRequest $getCenterRequest, GetCenterResponse $getCenterResponse) {
        $getCenterRequest->code = $code;
        $getCenter = App::make('GetCenter');
        $getCenter->execute($getCenterRequest, $getCenterResponse);
        return response()->json($getCenterResponse->body, $getCenterResponse->status);
    }
}
