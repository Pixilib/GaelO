<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateStudy\CreateStudy;
use App\GaelO\UseCases\CreateStudy\CreateStudyRequest;
use App\GaelO\UseCases\CreateStudy\CreateStudyResponse;
use App\GaelO\UseCases\DeleteStudy\DeleteStudy;
use App\GaelO\UseCases\DeleteStudy\DeleteStudyQuery;
use App\GaelO\UseCases\DeleteStudy\DeleteStudyRequest;
use App\GaelO\UseCases\DeleteStudy\DeleteStudyResponse;
use App\GaelO\UseCases\GetStudy\GetStudy;
use App\GaelO\UseCases\GetStudy\GetStudyRequest;
use App\GaelO\UseCases\GetStudy\GetStudyResponse;
use App\GaelO\UseCases\GetStudyDetails\GetStudyDetails;
use App\GaelO\UseCases\GetStudyDetails\GetStudyDetailsRequest;
use App\GaelO\UseCases\GetStudyDetails\GetStudyDetailsResponse;
use App\GaelO\UseCases\ReactivateStudy\ReactivateStudy;
use App\GaelO\UseCases\ReactivateStudy\ReactivateStudyRequest;
use App\GaelO\UseCases\ReactivateStudy\ReactivateStudyResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudyController extends Controller
{
    public function createStudy(Request $request, CreateStudy $createStudy, CreateStudyRequest $createStudyRequest, CreateStudyResponse $createStudyResponse){

        $currentUser = Auth::user();
        $requestData = $request->all();
        $createStudyRequest = Util::fillObject($requestData, $createStudyRequest);
        $createStudyRequest->currentUserId = $currentUser['id'];
        $createStudy->execute($createStudyRequest, $createStudyResponse);

        return response()->noContent()
                ->setStatusCode($createStudyResponse->status, $createStudyResponse->statusText);

    }

    public function getStudy(Request $request, GetStudy $getStudy, GetStudyRequest $getStudyRequest, GetStudyResponse $getStudyResponse, GetStudyDetails $getStudyDetails, GetStudyDetailsRequest $getStudyDetailsRequest, GetStudyDetailsResponse $getStudyDetailsResponse){
        //RECUPERATION DES QUERY PARAM (? dans URL)
        $queryParam = $request->query();
        if(array_key_exists('expand', $queryParam) ){
            $getStudyDetails->execute($getStudyDetailsRequest, $getStudyDetailsResponse);
            return response()->json($getStudyDetailsResponse->body)
            ->setStatusCode($getStudyDetailsResponse->status, $getStudyDetailsResponse->statusText);
        }else {
            $getStudy->execute($getStudyRequest, $getStudyResponse);
            return response()->json($getStudyResponse->body)
            ->setStatusCode($getStudyResponse->status, $getStudyResponse->statusText);
        }

    }

    public function deleteStudy(String $studyName, DeleteStudy $deleteStudy,  DeleteStudyRequest $deleteStudyRequest, DeleteStudyResponse $deleteStudyResponse){
        $currentUser = Auth::user();
        $deleteStudyRequest->currentUserId = $currentUser['id'];
        $deleteStudyRequest->studyName = $studyName;
        $deleteStudy->execute($deleteStudyRequest, $deleteStudyResponse);

        return response()->noContent()
                ->setStatusCode($deleteStudyResponse->status, $deleteStudyResponse->statusText);

    }

    public function reactivateStudy(string $studyName, ReactivateStudy $reactivateStudy, ReactivateStudyRequest $reactivateStudyRequest, ReactivateStudyResponse $reactivateStudyResponse){
        $currentUser = Auth::user();
        $reactivateStudyRequest->currentUserId = $currentUser['id'];
        $reactivateStudyRequest->studyName = $studyName;
        $reactivateStudy->execute($reactivateStudyRequest, $reactivateStudyResponse);
        return response()->noContent()
                ->setStatusCode($reactivateStudyResponse->status, $reactivateStudyResponse->statusText);
    }

}
