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
    public function createVisitType(Request $request, CreateVisitType $createVisitType,
        CreateVisitTypeRequest $createVisitTypeRequest, CreateVisitTypeResponse $createVisitTypeResponse, int $visitGroupId){

        $currentUser = Auth::user();
        $requestData = $request->all();
        
        Util::fillObject($requestData, $createVisitTypeRequest);
        $createVisitTypeRequest->currentUserId = $currentUser['id'];
        $createVisitTypeRequest->visitGroupId = $visitGroupId;

        $createVisitType->execute($createVisitTypeRequest, $createVisitTypeResponse);
        return $this->getJsonResponse($createVisitTypeResponse->body, $createVisitTypeResponse->status, $createVisitTypeResponse->statusText);
    }

    public function getVisitType(GetVisitType $getVisitType, GetVisitTypeRequest $getVisitTypeRequest, GetVisitTypeResponse $getVisitTypeResponse, int $visitTypeId){
        $currentUser = Auth::user();
        $getVisitTypeRequest->currentUserId = $currentUser['id'];
        $getVisitTypeRequest->visitTypeId = $visitTypeId;
        $getVisitType->execute($getVisitTypeRequest, $getVisitTypeResponse);
        return $this->getJsonResponse($getVisitTypeResponse->body, $getVisitTypeResponse->status, $getVisitTypeResponse->statusText);
    }

    public function deleteVisitType(DeleteVisitType $deleteVisitType, DeleteVisitTypeRequest $deleteVisitTypeRequest, DeleteVisitTypeResponse $deleteVisitTypeResponse, int $visitTypeId){
        $currentUser = Auth::user();
        $deleteVisitTypeRequest->currentUserId = $currentUser['id'];
        $deleteVisitTypeRequest->visitTypeId = $visitTypeId;
        $deleteVisitType->execute($deleteVisitTypeRequest, $deleteVisitTypeResponse);
        return $this->getJsonResponse($deleteVisitTypeResponse->body, $deleteVisitTypeResponse->status, $deleteVisitTypeResponse->statusText);
    }
}
