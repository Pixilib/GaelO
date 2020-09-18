<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateVisitGroup\CreateVisitGroup;
use App\GaelO\UseCases\CreateVisitGroup\CreateVisitGroupRequest;
use App\GaelO\UseCases\CreateVisitGroup\CreateVisitGroupResponse;
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

    public function testGetVisitGroupOfStudy(){

        //No groups should response empty array
        $response = $this->get('api/studies/'. $this->study[1]['name'].'/visit-groups')->content();
        $responseArray =  json_decode($response, true);
        $this->assertEmpty($responseArray);

        //Add a PT modality
        $this->visitGroup = factory(VisitGroup::class, 1)->create([
            'study_name'=> $this->study[1]['name'],
            'modality'=> 'PT']);

        //Should have a PT modality
        $response = $this->get('api/studies/'. $this->study[1]['name'].'/visit-groups')->content();
        $responseArray =  json_decode($response, true);
        $this->assertEquals('PT', $responseArray[0]);

    }
}
