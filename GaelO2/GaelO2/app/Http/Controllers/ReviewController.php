<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateInvestigatorForm\CreateInvestigatorForm;
use App\GaelO\UseCases\CreateInvestigatorForm\CreateInvestigatorFormRequest;
use App\GaelO\UseCases\CreateInvestigatorForm\CreateInvestigatorFormResponse;
use App\GaelO\UseCases\DeleteInvestigatorForm\DeleteInvestigatorForm;
use App\GaelO\UseCases\DeleteInvestigatorForm\DeleteInvestigatorFormRequest;
use App\GaelO\UseCases\DeleteInvestigatorForm\DeleteInvestigatorFormResponse;
use App\GaelO\UseCases\GetInvestigatorForm\GetInvestigatorForm;
use App\GaelO\UseCases\GetInvestigatorForm\GetInvestigatorFormRequest;
use App\GaelO\UseCases\GetInvestigatorForm\GetInvestigatorFormResponse;
use App\GaelO\UseCases\ModifyInvestigatorForm\ModifyInvestigatorForm;
use App\GaelO\UseCases\ModifyInvestigatorForm\ModifyInvestigatorFormRequest;
use App\GaelO\UseCases\ModifyInvestigatorForm\ModifyInvestigatorFormResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function getInvestigatorForm(int $visitId, Request $request, GetInvestigatorForm $getInvestigatorForm, GetInvestigatorFormRequest $getInvestigatorFormRequest, GetInvestigatorFormResponse $getInvestigatorFormResponse){

        $curentUser = Auth::user();
        $getInvestigatorFormRequest->currentUserId = $curentUser['id'];
        $getInvestigatorFormRequest->visitId = $visitId;

        $queryParam = $request->query();
        $getInvestigatorFormRequest->role = $queryParam['role'];

        $getInvestigatorForm->execute($getInvestigatorFormRequest, $getInvestigatorFormResponse);

        return response()->json($getInvestigatorFormResponse->body)
        ->setStatusCode($getInvestigatorFormResponse->status, $getInvestigatorFormResponse->statusText);
    }

    public function deleteInvestigatorForm(int $visitId, Request $request, DeleteInvestigatorForm $deleteInvestigatorForm, DeleteInvestigatorFormRequest $deleteInvestigatorFormRequest, DeleteInvestigatorFormResponse $deleteInvestigatorFormResponse){
        $curentUser = Auth::user();

        $deleteInvestigatorFormRequest->currentUserId = $curentUser['id'];
        $deleteInvestigatorFormRequest->visitId = $visitId;

        $requestData = $request->all();
        $deleteInvestigatorFromRequest = Util::fillObject($requestData, $deleteInvestigatorFormRequest);

        $deleteInvestigatorForm->execute($deleteInvestigatorFromRequest, $deleteInvestigatorFormResponse);

        if($deleteInvestigatorFormResponse->body != null){
            return response()->json($deleteInvestigatorFormResponse->body)
            ->setStatusCode($deleteInvestigatorFormResponse->status, $deleteInvestigatorFormResponse->statusText);
        } else {
            return response()->noContent()
            ->setStatusCode($deleteInvestigatorFormResponse->status, $deleteInvestigatorFormResponse->statusText);
        }


    }

    public function createInvestigatorForm(int $visitId, Request $request, CreateInvestigatorForm $createInvestigatorForm, CreateInvestigatorFormRequest $createInvestigatorFormRequest, CreateInvestigatorFormResponse $createInvestigatorFormResponse){

        $curentUser = Auth::user();

        $createInvestigatorFormRequest->currentUserId = $curentUser['id'];
        $createInvestigatorFormRequest->visitId = $visitId;

        $requestData = $request->all();
        $deleteInvestigatorFromRequest = Util::fillObject($requestData, $createInvestigatorFormRequest);

        $createInvestigatorForm->execute($deleteInvestigatorFromRequest, $createInvestigatorFormResponse);

        if($createInvestigatorFormResponse->body != null){
            return response()->json($createInvestigatorFormResponse->body)
            ->setStatusCode($createInvestigatorFormResponse->status, $createInvestigatorFormResponse->statusText);
        } else {
            return response()->noContent()
            ->setStatusCode($createInvestigatorFormResponse->status, $createInvestigatorFormResponse->statusText);
        }

    }

    public function modifyInvestigatorForm(int $visitId, Request $request, ModifyInvestigatorForm $modifyInvestigatorForm, ModifyInvestigatorFormRequest $modifyInvestigatorFormRequest, ModifyInvestigatorFormResponse $modifyInvestigatorFormResponse){

        $curentUser = Auth::user();

        $modifyInvestigatorFormRequest->currentUserId = $curentUser['id'];
        $modifyInvestigatorFormRequest->visitId = $visitId;

        $requestData = $request->all();
        $deleteInvestigatorFromRequest = Util::fillObject($requestData, $modifyInvestigatorFormRequest);

        $modifyInvestigatorForm->execute($deleteInvestigatorFromRequest, $modifyInvestigatorFormResponse);

        if($modifyInvestigatorFormResponse->body != null){
            return response()->json($modifyInvestigatorFormResponse->body)
            ->setStatusCode($modifyInvestigatorFormResponse->status, $modifyInvestigatorFormResponse->statusText);
        } else {
            return response()->noContent()
            ->setStatusCode($modifyInvestigatorFormResponse->status, $modifyInvestigatorFormResponse->statusText);
        }

    }


}
