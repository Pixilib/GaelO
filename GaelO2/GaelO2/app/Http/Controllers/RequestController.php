<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\Request\RequestRequest;
use App\GaelO\UseCases\Request\RequestResponse;
use App\GaelO\UseCases\Request\SendRequest;
use App\GaelO\Util;

use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function sendRequest(Request $request, RequestRequest $requestRequest, RequestResponse $requestResponse, SendRequest $sendRequest){
        $requestData = $request->all();
        $requestRequest = Util::fillObject($requestData, $requestRequest);
        $sendRequest->execute($requestRequest, $requestResponse);
        return response()->json($requestResponse->body)
                ->setStatusCode($requestResponse->status, $requestResponse->statusText);
    }
}
