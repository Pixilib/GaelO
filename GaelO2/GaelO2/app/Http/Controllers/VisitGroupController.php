<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateVisitGroup\CreateVisitGroup;
use App\GaelO\UseCases\CreateVisitGroup\CreateVisitGroupRequest;
use App\GaelO\UseCases\CreateVisitGroup\CreateVisitGroupResponse;
use App\GaelO\UseCases\DeleteVisitGroup\DeleteVisitGroup;
use App\GaelO\UseCases\DeleteVisitGroup\DeleteVisitGroupRequest;
use App\GaelO\UseCases\DeleteVisitGroup\DeleteVisitGroupResponse;
use App\GaelO\UseCases\GetVisitGroup\GetVisitGroup;
use App\GaelO\UseCases\GetVisitGroup\GetVisitGroupRequest;
use App\GaelO\UseCases\GetVisitGroup\GetVisitGroupResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VisitGroupController extends Controller
{
    public function createVisitGroup(String $studyName, Request $request, CreateVisitGroup $createVisitGroup, CreateVisitGroupRequest $createVisitGroupRequest, CreateVisitGroupResponse $createVisitGroupResponse) {
        $curentUser = Auth::user();
        $createVisitGroupRequest->currentUserId = $curentUser['id'];
        $createVisitGroupRequest->studyName = $studyName;
        $requestData = $request->all();
        $createVisitGroupRequest = Util::fillObject($requestData, $createVisitGroupRequest);
        $createVisitGroup->execute($createVisitGroupRequest, $createVisitGroupResponse);

        return response()->noContent()
                ->setStatusCode($createVisitGroupResponse->status, $createVisitGroupResponse->statusText);
    }

    public function getVisitGroup(int $visitGroupId, GetVisitGroup $getVisitGroup, GetVisitGroupRequest $getVisitGroupRequest, GetVisitGroupResponse $getVisitGroupResponse){
        $getVisitGroupRequest->visitGroupId = $visitGroupId;

        $getVisitGroup->execute($getVisitGroupRequest, $getVisitGroupResponse);

        return response()->json($getVisitGroupResponse->body)
                ->setStatusCode($getVisitGroupResponse->status, $getVisitGroupResponse->statusText);

    }

    public function deleteVisitGroup(int $visitGroupId, DeleteVisitGroup $deleteVisitGroup, DeleteVisitGroupRequest $deleteVisitGroupRequest, DeleteVisitGroupResponse $deleteVisitGroupResponse){
        $curentUser = Auth::user();
        $deleteVisitGroupRequest->currentUserId = $curentUser['id'];
        $deleteVisitGroupRequest->visitGroupId = $visitGroupId;
        $deleteVisitGroup->execute($deleteVisitGroupRequest, $deleteVisitGroupResponse);
        return response()->json($deleteVisitGroupResponse->body)
                ->setStatusCode($deleteVisitGroupResponse->status, $deleteVisitGroupResponse->statusText);

    }
}
