<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateInvestigatorForm\CreateInvestigatorForm;
use App\GaelO\UseCases\CreateInvestigatorForm\CreateInvestigatorFormRequest;
use App\GaelO\UseCases\CreateInvestigatorForm\CreateInvestigatorFormResponse;
use App\GaelO\UseCases\CreateReviewForm\CreateReview;
use App\GaelO\UseCases\CreateReviewForm\CreateReviewFormRequest;
use App\GaelO\UseCases\CreateReviewForm\CreateReviewFormResponse;
use App\GaelO\UseCases\DeleteInvestigatorForm\DeleteInvestigatorForm;
use App\GaelO\UseCases\DeleteInvestigatorForm\DeleteInvestigatorFormRequest;
use App\GaelO\UseCases\DeleteInvestigatorForm\DeleteInvestigatorFormResponse;
use App\GaelO\UseCases\DeleteReviewForm\DeleteReviewForm;
use App\GaelO\UseCases\DeleteReviewForm\DeleteReviewFormRequest;
use App\GaelO\UseCases\DeleteReviewForm\DeleteReviewFormResponse;
use App\GaelO\UseCases\GetInvestigatorForm\GetInvestigatorForm;
use App\GaelO\UseCases\GetInvestigatorForm\GetInvestigatorFormRequest;
use App\GaelO\UseCases\GetInvestigatorForm\GetInvestigatorFormResponse;
use App\GaelO\UseCases\GetReviewForm\GetReviewForm;
use App\GaelO\UseCases\GetReviewForm\GetReviewFormRequest;
use App\GaelO\UseCases\GetReviewForm\GetReviewFormResponse;
use App\GaelO\UseCases\GetReviewFormFromVisit\GetReviewFormFromVisit;
use App\GaelO\UseCases\GetReviewFormFromVisit\GetReviewFormFromVisitRequest;
use App\GaelO\UseCases\GetReviewFormFromVisit\GetReviewFormFromVisitResponse;
use App\GaelO\UseCases\ModifyInvestigatorForm\ModifyInvestigatorForm;
use App\GaelO\UseCases\ModifyInvestigatorForm\ModifyInvestigatorFormRequest;
use App\GaelO\UseCases\ModifyInvestigatorForm\ModifyInvestigatorFormResponse;
use App\GaelO\UseCases\ModifyReviewForm\ModifyReviewForm;
use App\GaelO\UseCases\ModifyReviewForm\ModifyReviewFormRequest;
use App\GaelO\UseCases\ModifyReviewForm\ModifyReviewFormResponse;
use App\GaelO\UseCases\UnlockInvestigatorForm\UnlockInvestigatorForm;
use App\GaelO\UseCases\UnlockInvestigatorForm\UnlockInvestigatorFormRequest;
use App\GaelO\UseCases\UnlockInvestigatorForm\UnlockInvestigatorFormResponse;
use App\GaelO\UseCases\UnlockReviewForm\UnlockReviewForm;
use App\GaelO\UseCases\UnlockReviewForm\UnlockReviewFormRequest;
use App\GaelO\UseCases\UnlockReviewForm\UnlockReviewFormResponse;
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
        $deleteInvestigatorFormRequest = Util::fillObject($requestData, $deleteInvestigatorFormRequest);

        $deleteInvestigatorForm->execute($deleteInvestigatorFormRequest, $deleteInvestigatorFormResponse);

        if($deleteInvestigatorFormResponse->body != null){
            return response()->json($deleteInvestigatorFormResponse->body)
            ->setStatusCode($deleteInvestigatorFormResponse->status, $deleteInvestigatorFormResponse->statusText);
        } else {
            return response()->noContent()
            ->setStatusCode($deleteInvestigatorFormResponse->status, $deleteInvestigatorFormResponse->statusText);
        }


    }

    public function unlockInvestigatorForm(int $visitId, Request $request, UnlockInvestigatorForm $unlockInvestigatorForm, UnlockInvestigatorFormRequest $unlockInvestigatorFormRequest, UnlockInvestigatorFormResponse $unlockInvestigatorFormResponse){
        $curentUser = Auth::user();

        $unlockInvestigatorFormRequest->currentUserId = $curentUser['id'];
        $unlockInvestigatorFormRequest->visitId = $visitId;

        $requestData = $request->all();
        $unlockInvestigatorFormRequest = Util::fillObject($requestData, $unlockInvestigatorFormRequest);

        $unlockInvestigatorForm->execute($unlockInvestigatorFormRequest, $unlockInvestigatorFormResponse);

        if($unlockInvestigatorFormResponse->body != null){
            return response()->json($unlockInvestigatorFormResponse->body)
            ->setStatusCode($unlockInvestigatorFormResponse->status, $unlockInvestigatorFormResponse->statusText);
        } else {
            return response()->noContent()
            ->setStatusCode($unlockInvestigatorFormResponse->status, $unlockInvestigatorFormResponse->statusText);
        }


    }

    public function createInvestigatorForm(int $visitId, Request $request, CreateInvestigatorForm $createInvestigatorForm, CreateInvestigatorFormRequest $createInvestigatorFormRequest, CreateInvestigatorFormResponse $createInvestigatorFormResponse){

        $curentUser = Auth::user();

        $createInvestigatorFormRequest->currentUserId = $curentUser['id'];
        $createInvestigatorFormRequest->visitId = $visitId;

        $requestData = $request->all();
        $createInvestigatorFormRequest = Util::fillObject($requestData, $createInvestigatorFormRequest);

        $createInvestigatorForm->execute($createInvestigatorFormRequest, $createInvestigatorFormResponse);

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
        $deleteInvestigatorFormRequest = Util::fillObject($requestData, $modifyInvestigatorFormRequest);

        $modifyInvestigatorForm->execute($deleteInvestigatorFormRequest, $modifyInvestigatorFormResponse);

        if($modifyInvestigatorFormResponse->body != null){
            return response()->json($modifyInvestigatorFormResponse->body)
            ->setStatusCode($modifyInvestigatorFormResponse->status, $modifyInvestigatorFormResponse->statusText);
        } else {
            return response()->noContent()
            ->setStatusCode($modifyInvestigatorFormResponse->status, $modifyInvestigatorFormResponse->statusText);
        }

    }

    public function createReviewForm(string $studyName, int $visitId, Request $request, CreateReview $createReview, CreateReviewFormRequest $createReviewFormRequest, CreateReviewFormResponse $createReviewFormResponse){
        $curentUser = Auth::user();
        $requestData = $request->all();

        $createReviewFormRequest->studyName = $studyName;
        $createReviewFormRequest->visitId = $visitId;
        $createReviewFormRequest->currentUserId = $curentUser['id'];

        $createReviewFormRequest = Util::fillObject($requestData, $createReviewFormRequest);

        $createReview->execute($createReviewFormRequest, $createReviewFormResponse);

        if($createReviewFormResponse->body != null){
            return response()->json($createReviewFormResponse->body)
            ->setStatusCode($createReviewFormResponse->status, $createReviewFormResponse->statusText);
        } else {
            return response()->noContent()
            ->setStatusCode($createReviewFormResponse->status, $createReviewFormResponse->statusText);
        }

    }

    public function modifyReviewForm(int $reviewId, Request $request, ModifyReviewForm $modifyReviewForm, ModifyReviewFormRequest $modifyReviewFormRequest, ModifyReviewFormResponse $modifyReviewFormResponse ){

        $curentUser = Auth::user();
        $requestData = $request->all();

        $modifyReviewFormRequest->reviewId = $reviewId;
        $modifyReviewFormRequest->currentUserId = $curentUser['id'];
        $modifyReviewFormRequest = Util::fillObject($requestData, $modifyReviewFormRequest);

        $modifyReviewForm->execute($modifyReviewFormRequest, $modifyReviewFormResponse);

        if($modifyReviewFormResponse->body != null){
            return response()->json($modifyReviewFormResponse->body)
            ->setStatusCode($modifyReviewFormResponse->status, $modifyReviewFormResponse->statusText);
        } else {
            return response()->noContent()
            ->setStatusCode($modifyReviewFormResponse->status, $modifyReviewFormResponse->statusText);
        }

    }

    public function getReviewForm(int $reviewId, GetReviewForm $getReviewForm, GetReviewFormRequest $getReviewFormRequest, GetReviewFormResponse $getReviewFormResponse){

        $curentUser = Auth::user();
        $getReviewFormRequest->currentUserId = $curentUser['id'];
        $getReviewFormRequest->reviewId = $reviewId;

        $getReviewForm->execute($getReviewFormRequest, $getReviewFormResponse);

        return response()->json($getReviewFormResponse->body)
            ->setStatusCode($getReviewFormResponse->status, $getReviewFormResponse->statusText);

    }

    public function getReviewsFromVisit(string $studyName, int $visitId, GetReviewFormFromVisit $getReviewFormFromVisit, GetReviewFormFromVisitRequest $getReviewFormFromVisitRequest, GetReviewFormFromVisitResponse $getReviewFormFromVisitResponse){
        $curentUser = Auth::user();

        $getReviewFormFromVisitRequest->currentUserId = $curentUser['id'];
        $getReviewFormFromVisitRequest->studyName = $studyName;
        $getReviewFormFromVisitRequest->visitId = $visitId;

        $getReviewFormFromVisit->execute($getReviewFormFromVisitRequest, $getReviewFormFromVisitResponse);

        return response()->json($getReviewFormFromVisitResponse->body)
        ->setStatusCode($getReviewFormFromVisitResponse->status, $getReviewFormFromVisitResponse->statusText);
    }

    public function deleteReviewForm(int $reviewId, Request $request, DeleteReviewForm $deleteReviewForm, DeleteReviewFormRequest $deleteReviewFormRequest, DeleteReviewFormResponse $deleteReviewFormResponse){
        $curentUser = Auth::user();
        $requestData = $request->all();

        $deleteReviewFormRequest->currentUserId = $curentUser['id'];
        $deleteReviewFormRequest->reviewId = $reviewId;

        $deleteReviewFormRequest = Util::fillObject($requestData, $deleteReviewFormRequest);

        $deleteReviewForm->execute($deleteReviewFormRequest, $deleteReviewFormResponse);

        if($deleteReviewFormResponse->body != null){
            return response()->json($deleteReviewFormResponse->body)
            ->setStatusCode($deleteReviewFormResponse->status, $deleteReviewFormResponse->statusText);
        } else {
            return response()->noContent()
            ->setStatusCode($deleteReviewFormResponse->status, $deleteReviewFormResponse->statusText);
        }

    }

    public function unlockReviewForm(int $reviewId, Request $request, UnlockReviewForm $unlockReviewForm, UnlockReviewFormRequest $unlockReviewFormRequest, UnlockReviewFormResponse $unlockReviewFormResponse){
        $curentUser = Auth::user();
        $requestData = $request->all();

        $unlockReviewFormRequest->currentUserId = $curentUser['id'];
        $unlockReviewFormRequest->reviewId = $reviewId;

        $deleteReviewFormRequest = Util::fillObject($requestData, $unlockReviewFormRequest);

        $unlockReviewForm->execute($deleteReviewFormRequest, $unlockReviewFormResponse);

        if($unlockReviewFormResponse->body != null){
            return response()->json($unlockReviewFormResponse->body)
            ->setStatusCode($unlockReviewFormResponse->status, $unlockReviewFormResponse->statusText);
        } else {
            return response()->noContent()
            ->setStatusCode($unlockReviewFormResponse->status, $unlockReviewFormResponse->statusText);
        }

    }




}
