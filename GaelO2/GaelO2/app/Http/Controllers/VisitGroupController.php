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
        $currentUser = Auth::user();
        $createVisitGroupRequest->currentUserId = $currentUser['id'];
        $createVisitGroupRequest->studyName = $studyName;
        $requestData = $request->all();
        $createVisitGroupRequest = Util::fillObject($requestData, $createVisitGroupRequest);
        $createVisitGroup->execute($createVisitGroupRequest, $createVisitGroupResponse);

        return $this->getJsonResponse($createVisitGroupResponse->body, $createVisitGroupResponse->status, $createVisitGroupResponse->statusText);
    }

    public function getVisitGroup(int $visitGroupId, GetVisitGroup $getVisitGroup, GetVisitGroupRequest $getVisitGroupRequest, GetVisitGroupResponse $getVisitGroupResponse){
        $currentUser = Auth::user();
        $getVisitGroupRequest->currentUserId = $currentUser['id'];
        $getVisitGroupRequest->visitGroupId = $visitGroupId;

        $getVisitGroup->execute($getVisitGroupRequest, $getVisitGroupResponse);

        return $this->getJsonResponse($getVisitGroupResponse->body, $getVisitGroupResponse->status, $getVisitGroupResponse->statusText);

    }

    public function deleteVisitGroup(int $visitGroupId, DeleteVisitGroup $deleteVisitGroup, DeleteVisitGroupRequest $deleteVisitGroupRequest, DeleteVisitGroupResponse $deleteVisitGroupResponse){
        $currentUser = Auth::user();
        $deleteVisitGroupRequest->currentUserId = $currentUser['id'];
        $deleteVisitGroupRequest->visitGroupId = $visitGroupId;
        $deleteVisitGroup->execute($deleteVisitGroupRequest, $deleteVisitGroupResponse);
        return $this->getJsonResponse($deleteVisitGroupResponse->body, $deleteVisitGroupResponse->status, $deleteVisitGroupResponse->statusText);

    }
}
