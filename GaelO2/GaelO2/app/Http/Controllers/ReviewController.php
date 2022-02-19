<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateFileToForm\CreateFileToForm;
use App\GaelO\UseCases\CreateFileToForm\CreateFileToFormRequest;
use App\GaelO\UseCases\CreateFileToForm\CreateFileToFormResponse;
use App\GaelO\UseCases\CreateInvestigatorForm\CreateInvestigatorForm;
use App\GaelO\UseCases\CreateInvestigatorForm\CreateInvestigatorFormRequest;
use App\GaelO\UseCases\CreateInvestigatorForm\CreateInvestigatorFormResponse;
use App\GaelO\UseCases\CreateReviewForm\CreateReview;
use App\GaelO\UseCases\CreateReviewForm\CreateReviewFormRequest;
use App\GaelO\UseCases\CreateReviewForm\CreateReviewFormResponse;
use App\GaelO\UseCases\DeleteFileOfForm\DeleteFileOfForm;
use App\GaelO\UseCases\DeleteFileOfForm\DeleteFileOfFormRequest;
use App\GaelO\UseCases\DeleteFileOfForm\DeleteFileOfFormResponse;
use App\GaelO\UseCases\DeleteInvestigatorForm\DeleteInvestigatorForm;
use App\GaelO\UseCases\DeleteInvestigatorForm\DeleteInvestigatorFormRequest;
use App\GaelO\UseCases\DeleteInvestigatorForm\DeleteInvestigatorFormResponse;
use App\GaelO\UseCases\DeleteReviewForm\DeleteReviewForm;
use App\GaelO\UseCases\DeleteReviewForm\DeleteReviewFormRequest;
use App\GaelO\UseCases\DeleteReviewForm\DeleteReviewFormResponse;
use App\GaelO\UseCases\GetAssociatedDataForInvestigator\GetAssociatedDataForInvestigator;
use App\GaelO\UseCases\GetAssociatedDataForInvestigator\GetAssociatedDataForInvestigatorRequest;
use App\GaelO\UseCases\GetAssociatedDataForInvestigator\GetAssociatedDataForInvestigatorResponse;
use App\GaelO\UseCases\GetAssociatedDataForReview\GetAssociatedDataForReview;
use App\GaelO\UseCases\GetAssociatedDataForReview\GetAssociatedDataForReviewRequest;
use App\GaelO\UseCases\GetAssociatedDataForReview\GetAssociatedDataForReviewResponse;
use App\GaelO\UseCases\GetFileOfForm\GetFileOfForm;
use App\GaelO\UseCases\GetFileOfForm\GetFileOfFormRequest;
use App\GaelO\UseCases\GetFileOfForm\GetFileOfFormResponse;
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
    public function getInvestigatorForm(Request $request, GetInvestigatorForm $getInvestigatorForm, GetInvestigatorFormRequest $getInvestigatorFormRequest, GetInvestigatorFormResponse $getInvestigatorFormResponse, int $visitId)
    {

        $currentUser = Auth::user();
        $getInvestigatorFormRequest->currentUserId = $currentUser['id'];
        $getInvestigatorFormRequest->visitId = $visitId;

        $queryParam = $request->query();
        $getInvestigatorFormRequest->role = $queryParam['role'];
        $getInvestigatorFormRequest->studyName = $queryParam['studyName'];

        $getInvestigatorForm->execute($getInvestigatorFormRequest, $getInvestigatorFormResponse);

        return $this->getJsonResponse($getInvestigatorFormResponse->body, $getInvestigatorFormResponse->status, $getInvestigatorFormResponse->statusText);
    }

    public function deleteInvestigatorForm(Request $request, DeleteInvestigatorForm $deleteInvestigatorForm, DeleteInvestigatorFormRequest $deleteInvestigatorFormRequest, DeleteInvestigatorFormResponse $deleteInvestigatorFormResponse, int $visitId)
    {
        $currentUser = Auth::user();

        $deleteInvestigatorFormRequest->currentUserId = $currentUser['id'];
        $deleteInvestigatorFormRequest->visitId = $visitId;

        $requestData = $request->all();
        $deleteInvestigatorFormRequest = Util::fillObject($requestData, $deleteInvestigatorFormRequest);

        $deleteInvestigatorForm->execute($deleteInvestigatorFormRequest, $deleteInvestigatorFormResponse);

        return $this->getJsonResponse($deleteInvestigatorFormResponse->body, $deleteInvestigatorFormResponse->status, $deleteInvestigatorFormResponse->statusText);
    }

    public function unlockInvestigatorForm(Request $request, UnlockInvestigatorForm $unlockInvestigatorForm, UnlockInvestigatorFormRequest $unlockInvestigatorFormRequest, UnlockInvestigatorFormResponse $unlockInvestigatorFormResponse, int $visitId)
    {
        $currentUser = Auth::user();

        $unlockInvestigatorFormRequest->currentUserId = $currentUser['id'];
        $unlockInvestigatorFormRequest->visitId = $visitId;

        $requestData = $request->all();
        $unlockInvestigatorFormRequest = Util::fillObject($requestData, $unlockInvestigatorFormRequest);

        $unlockInvestigatorForm->execute($unlockInvestigatorFormRequest, $unlockInvestigatorFormResponse);

        return $this->getJsonResponse($unlockInvestigatorFormResponse->body, $unlockInvestigatorFormResponse->status, $unlockInvestigatorFormResponse->statusText);
    }

    public function createInvestigatorForm(Request $request, CreateInvestigatorForm $createInvestigatorForm, CreateInvestigatorFormRequest $createInvestigatorFormRequest, CreateInvestigatorFormResponse $createInvestigatorFormResponse, int $visitId)
    {

        $currentUser = Auth::user();

        $createInvestigatorFormRequest->currentUserId = $currentUser['id'];
        $createInvestigatorFormRequest->visitId = $visitId;

        $requestData = $request->all();
        $createInvestigatorFormRequest = Util::fillObject($requestData, $createInvestigatorFormRequest);

        $createInvestigatorForm->execute($createInvestigatorFormRequest, $createInvestigatorFormResponse);

        return $this->getJsonResponse($createInvestigatorFormResponse->body, $createInvestigatorFormResponse->status, $createInvestigatorFormResponse->statusText);
    }

    public function modifyInvestigatorForm(Request $request, ModifyInvestigatorForm $modifyInvestigatorForm, ModifyInvestigatorFormRequest $modifyInvestigatorFormRequest, ModifyInvestigatorFormResponse $modifyInvestigatorFormResponse, int $visitId)
    {

        $currentUser = Auth::user();

        $modifyInvestigatorFormRequest->currentUserId = $currentUser['id'];
        $modifyInvestigatorFormRequest->visitId = $visitId;

        $requestData = $request->all();
        $deleteInvestigatorFormRequest = Util::fillObject($requestData, $modifyInvestigatorFormRequest);

        $modifyInvestigatorForm->execute($deleteInvestigatorFormRequest, $modifyInvestigatorFormResponse);

        return $this->getJsonResponse($modifyInvestigatorFormResponse->body, $modifyInvestigatorFormResponse->status, $modifyInvestigatorFormResponse->statusText);
    }

    public function createReviewForm(Request $request, CreateReview $createReview, CreateReviewFormRequest $createReviewFormRequest, CreateReviewFormResponse $createReviewFormResponse, int $visitId)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();
        $queryParam = $request->query();
        $createReviewFormRequest->studyName = $queryParam['studyName'];

        $createReviewFormRequest->visitId = $visitId;
        $createReviewFormRequest->currentUserId = $currentUser['id'];

        $createReviewFormRequest = Util::fillObject($requestData, $createReviewFormRequest);

        $createReview->execute($createReviewFormRequest, $createReviewFormResponse);

        return $this->getJsonResponse($createReviewFormResponse->body, $createReviewFormResponse->status, $createReviewFormResponse->statusText);
    }

    public function modifyReviewForm(Request $request, ModifyReviewForm $modifyReviewForm, ModifyReviewFormRequest $modifyReviewFormRequest, ModifyReviewFormResponse $modifyReviewFormResponse, int $reviewId)
    {

        $currentUser = Auth::user();
        $requestData = $request->all();

        $modifyReviewFormRequest->reviewId = $reviewId;
        $modifyReviewFormRequest->currentUserId = $currentUser['id'];
        $modifyReviewFormRequest = Util::fillObject($requestData, $modifyReviewFormRequest);

        $modifyReviewForm->execute($modifyReviewFormRequest, $modifyReviewFormResponse);

        return $this->getJsonResponse($modifyReviewFormResponse->body, $modifyReviewFormResponse->status, $modifyReviewFormResponse->statusText);
    }

    public function getReviewForm(GetReviewForm $getReviewForm, GetReviewFormRequest $getReviewFormRequest, GetReviewFormResponse $getReviewFormResponse, int $reviewId)
    {
        $currentUser = Auth::user();
        $getReviewFormRequest->currentUserId = $currentUser['id'];
        $getReviewFormRequest->reviewId = $reviewId;

        $getReviewForm->execute($getReviewFormRequest, $getReviewFormResponse);

        return $this->getJsonResponse($getReviewFormResponse->body, $getReviewFormResponse->status, $getReviewFormResponse->statusText);
    }

    public function getReviewsFromVisit(Request $request, GetReviewFormFromVisit $getReviewFormFromVisit, GetReviewFormFromVisitRequest $getReviewFormFromVisitRequest, GetReviewFormFromVisitResponse $getReviewFormFromVisitResponse, int $visitId)
    {
        $currentUser = Auth::user();

        $getReviewFormFromVisitRequest->currentUserId = $currentUser['id'];
        $getReviewFormFromVisitRequest->visitId = $visitId;

        $queryParam = $request->query();
        $getReviewFormFromVisitRequest->studyName = $queryParam['studyName'];

        if( array_key_exists('userId', $queryParam) ) $getReviewFormFromVisitRequest->userId = $queryParam['userId'];

        $getReviewFormFromVisit->execute($getReviewFormFromVisitRequest, $getReviewFormFromVisitResponse);

        return $this->getJsonResponse($getReviewFormFromVisitResponse->body, $getReviewFormFromVisitResponse->status, $getReviewFormFromVisitResponse->statusText);
    }

    public function deleteReviewForm(Request $request, DeleteReviewForm $deleteReviewForm, DeleteReviewFormRequest $deleteReviewFormRequest, DeleteReviewFormResponse $deleteReviewFormResponse, int $reviewId)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        $deleteReviewFormRequest->currentUserId = $currentUser['id'];
        $deleteReviewFormRequest->reviewId = $reviewId;

        $deleteReviewFormRequest = Util::fillObject($requestData, $deleteReviewFormRequest);

        $deleteReviewForm->execute($deleteReviewFormRequest, $deleteReviewFormResponse);

        return $this->getJsonResponse($deleteReviewFormResponse->body, $deleteReviewFormResponse->status, $deleteReviewFormResponse->statusText);
    }

    public function unlockReviewForm(Request $request, UnlockReviewForm $unlockReviewForm, UnlockReviewFormRequest $unlockReviewFormRequest, UnlockReviewFormResponse $unlockReviewFormResponse, int $reviewId)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();

        $unlockReviewFormRequest->currentUserId = $currentUser['id'];
        $unlockReviewFormRequest->reviewId = $reviewId;

        $deleteReviewFormRequest = Util::fillObject($requestData, $unlockReviewFormRequest);

        $unlockReviewForm->execute($deleteReviewFormRequest, $unlockReviewFormResponse);

        return $this->getJsonResponse($unlockReviewFormResponse->body, $unlockReviewFormResponse->status, $unlockReviewFormResponse->statusText);
    }

    public function createReviewFile(Request $request, CreateFileToForm $createFileToForm, CreateFileToFormRequest $createFileToFormRequest, CreateFileToFormResponse $createFileToFormResponse, int $reviewId, string $key)
    {

        $currentUser = Auth::user();
        $requestData = $request->input();

        $createFileToFormRequest->currentUserId = $currentUser['id'];
        $createFileToFormRequest->id = $reviewId;
        $createFileToFormRequest->key = $key;
        $createFileToFormRequest->contentType = $request->headers->get('Content-Type');
        $createFileToFormRequest->binaryData = $requestData[0];

        $createFileToForm->execute($createFileToFormRequest, $createFileToFormResponse);

        return $this->getJsonResponse($createFileToFormResponse->body, $createFileToFormResponse->status, $createFileToFormResponse->statusText);
    }

    public function deleteReviewFile(DeleteFileOfForm $deleteFileOfForm, DeleteFileOfFormRequest $deleteFileOfFormRequest, DeleteFileOfFormResponse $deleteFileOfFormResponse, int $reviewId, string $key)
    {

        $currentUser = Auth::user();

        $deleteFileOfFormRequest->currentUserId = $currentUser['id'];
        $deleteFileOfFormRequest->key = $key;
        $deleteFileOfFormRequest->id = $reviewId;

        $deleteFileOfForm->execute($deleteFileOfFormRequest, $deleteFileOfFormResponse);

        return $this->getJsonResponse($deleteFileOfFormResponse->body, $deleteFileOfFormResponse->status, $deleteFileOfFormResponse->statusText);
    }

    public function getReviewFile(GetFileOfForm $getFileOfForm, GetFileOfFormRequest $getFileOfFormRequest, GetFileOfFormResponse $getFileOfFormResponse, int $reviewId, string $key)
    {

        $currentUser = Auth::user();
        $getFileOfFormRequest->currentUserId = $currentUser['id'];
        $getFileOfFormRequest->key = $key;
        $getFileOfFormRequest->id = $reviewId;

        $getFileOfForm->execute($getFileOfFormRequest, $getFileOfFormResponse);

        return $this->getJsonResponse($getFileOfFormResponse->body, $getFileOfFormResponse->status, $getFileOfFormResponse->statusText);
    }

    public function getAssociatedDataOfVisitForReviewer(GetAssociatedDataForReview $getAssociatedDataForReview, GetAssociatedDataForReviewRequest $getAssociatedDataForReviewRequest, GetAssociatedDataForReviewResponse $getAssociatedDataForReviewResponse, string $studyName, int $visitId)
    {
        $currentUser = Auth::user();
        $getAssociatedDataForReviewRequest->currentUserId = $currentUser['id'];
        $getAssociatedDataForReviewRequest->studyName = $studyName;
        $getAssociatedDataForReviewRequest->visitId = $visitId;

        $getAssociatedDataForReview->execute($getAssociatedDataForReviewRequest, $getAssociatedDataForReviewResponse);

        return $this->getJsonResponse($getAssociatedDataForReviewResponse->body, $getAssociatedDataForReviewResponse->status, $getAssociatedDataForReviewResponse->statusText);
    }

    public function getAssociatedDataOfVisitForInvestigator(Request $request, GetAssociatedDataForInvestigator $getAssociatedDataForInvestigator, GetAssociatedDataForInvestigatorRequest $getAssociatedDataForInvestigatorRequest, GetAssociatedDataForInvestigatorResponse $getAssociatedDataForInvestigatorResponse, int $visitId)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getAssociatedDataForInvestigatorRequest->currentUserId = $currentUser['id'];
        $getAssociatedDataForInvestigatorRequest->visitId = $visitId;
        $getAssociatedDataForInvestigatorRequest->role = $queryParam['role'];

        $getAssociatedDataForInvestigator->execute($getAssociatedDataForInvestigatorRequest, $getAssociatedDataForInvestigatorResponse);

        return $this->getJsonResponse($getAssociatedDataForInvestigatorResponse->body, $getAssociatedDataForInvestigatorResponse->status, $getAssociatedDataForInvestigatorResponse->statusText);
    }
}
