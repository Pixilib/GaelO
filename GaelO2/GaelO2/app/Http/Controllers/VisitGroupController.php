<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateVisitGroup\CreateVisitGroup;
use App\GaelO\UseCases\CreateVisitGroup\CreateVisitGroupRequest;
use App\GaelO\UseCases\CreateVisitGroup\CreateVisitGroupResponse;
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
}
