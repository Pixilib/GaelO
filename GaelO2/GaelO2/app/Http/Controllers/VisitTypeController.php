<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateVisitType\CreateVisitType;
use App\GaelO\UseCases\CreateVisitType\CreateVisitTypeRequest;
use App\GaelO\UseCases\CreateVisitType\CreateVisitTypeResponse;
use App\GaelO\UseCases\DeleteVisitType\DeleteVisitType;
use App\GaelO\UseCases\DeleteVisitType\DeleteVisitTypeRequest;
use App\GaelO\UseCases\DeleteVisitType\DeleteVisitTypeResponse;
use App\GaelO\UseCases\GetVisitType\GetVisitType;
use App\GaelO\UseCases\GetVisitType\GetVisitTypeRequest;
use App\GaelO\UseCases\GetVisitType\GetVisitTypeResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VisitTypeController extends Controller
{
    public function createVisitType(int $visitGroupId, Request $request, CreateVisitType $createVisitType,
        CreateVisitTypeRequest $createVisitTypeRequest, CreateVisitTypeResponse $createVisitTypeResponse){

        $curentUser = Auth::user();
        $createVisitTypeRequest->currentUserId = $curentUser['id'];
        $createVisitTypeRequest->visitGroupId = $visitGroupId;

        $requestData = $request->all();
        $createVisitTypeRequest = Util::fillObject($requestData, $createVisitTypeRequest);

        $createVisitType->execute($createVisitTypeRequest, $createVisitTypeResponse);

        return response()->noContent()
                ->setStatusCode($createVisitTypeResponse->status, $createVisitTypeResponse->statusText);

    }

    public function getVisitType(int $visitTypeId, GetVisitType $getVisitType, GetVisitTypeRequest $getVisitTypeRequest, GetVisitTypeResponse $getVisitTypeResponse){
        $getVisitTypeRequest->visitTypeId = $visitTypeId;
        $getVisitType->execute($getVisitTypeRequest, $getVisitTypeResponse);
        return response()->json($getVisitTypeResponse->body)
            ->setStatusCode($getVisitTypeResponse->status, $getVisitTypeResponse->statusText);
    }

    public function deleteVisitType(int $visitTypeId, DeleteVisitType $deleteVisitType, DeleteVisitTypeRequest $deleteVisitTypeRequest, DeleteVisitTypeResponse $deleteVisitTypeResponse){
        $deleteVisitTypeRequest->visitTypeId = $visitTypeId;
        $deleteVisitType->execute($deleteVisitTypeRequest, $deleteVisitTypeResponse);
        return response()->noContent()
        ->setStatusCode($deleteVisitTypeResponse->status, $deleteVisitTypeResponse->statusText);
    }
}
