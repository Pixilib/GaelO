<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetInvestigatorForm\GetInvestigatorForm;
use App\GaelO\UseCases\GetInvestigatorForm\GetInvestigatorFormRequest;
use App\GaelO\UseCases\GetInvestigatorForm\GetInvestigatorFormResponse;
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
}
