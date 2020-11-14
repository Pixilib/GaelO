<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateDocumentation\CreateDocumentation;
use App\GaelO\UseCases\CreateDocumentation\CreateDocumentationRequest;
use App\GaelO\UseCases\CreateDocumentation\CreateDocumentationResponse;
use App\GaelO\UseCases\DeleteDocumentation\DeleteDocumentation;
use App\GaelO\UseCases\DeleteDocumentation\DeleteDocumentationRequest;
use App\GaelO\UseCases\DeleteDocumentation\DeleteDocumentationResponse;
use App\GaelO\UseCases\GetDocumentation\GetDocumentation;
use App\GaelO\UseCases\GetDocumentation\GetDocumentationRequest;
use App\GaelO\UseCases\GetDocumentation\GetDocumentationResponse;
use App\GaelO\UseCases\StoreDocumentationFile\StoreDocumentationFile;
use App\GaelO\UseCases\StoreDocumentationFile\StoreDocumentationFileRequest;
use App\GaelO\UseCases\StoreDocumentationFile\StoreDocumentationFileResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentationController extends Controller
{
    public function createDocumentation(string $studyName='', Request $request, CreateDocumentation $createDocumentation, CreateDocumentationRequest $createDocumentationRequest, CreateDocumentationResponse $createDocumentationResponse) {
        $currentUser = Auth::user();
        $requestData = $request->all();
        $createDocumentationRequest = Util::fillObject($requestData, $createDocumentationRequest);
        $createDocumentationRequest->currentUserId = $currentUser['id'];
        $createDocumentationRequest->studyName = $studyName;
        $createDocumentation->execute($createDocumentationRequest, $createDocumentationResponse);
        return response()->json($createDocumentationResponse->body)
                ->setStatusCode($createDocumentationResponse->status, $createDocumentationResponse->statusText);
    }

    public function uploadDocumentation(int $documentationId, Request $request, StoreDocumentationFile $storeDocumentationFile, StoreDocumentationFileRequest $storeDocumentationFileRequest, StoreDocumentationFileResponse $storeDocumentationFileResponse){
        $currentUser = Auth::user();
        $requestData = $request->all();
        $storeDocumentationFileRequest->currentUserId = $currentUser['id'];
        $storeDocumentationFileRequest->id = $documentationId;
        $storeDocumentationFileRequest->contentType = $request->headers->get('Content-Type');
        $storeDocumentationFileRequest = Util::fillObject($requestData, $storeDocumentationFileRequest);
        $storeDocumentationFile->execute($storeDocumentationFileRequest, $storeDocumentationFileResponse);
        return response()->json($storeDocumentationFileResponse->body)
                ->setStatusCode($storeDocumentationFileResponse->status, $storeDocumentationFileResponse->statusText);

    }

    public function deleteDocumentation(int $documentationId, DeleteDocumentation $deleteDocumentation, DeleteDocumentationRequest $deleteDocumentationRequest, DeleteDocumentationResponse $deleteDocumentationResponse){
        $currentUser = Auth::user();
        $deleteDocumentationRequest->id = $documentationId;
        $deleteDocumentationRequest->currentUserId = $currentUser['id'];
        $deleteDocumentation->execute($deleteDocumentationRequest, $deleteDocumentationResponse);

        return response()->noContent()
                ->setStatusCode($deleteDocumentationResponse->status, $deleteDocumentationResponse->statusText);

    }

    public function getDocumentationsFromStudy(string $studyName, Request $request, GetDocumentation $getDocumentation, GetDocumentationRequest $getDocumentationRequest, GetDocumentationResponse $getDocumentationResponse){
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getDocumentationRequest->role = $queryParam['role'];
        $getDocumentationRequest->studyName = $studyName;
        $getDocumentationRequest->currentUserId = $currentUser['id'];
        $getDocumentation->execute($getDocumentationRequest, $getDocumentationResponse);
        return response()->json($getDocumentationResponse->body)
                ->setStatusCode($getDocumentationResponse->status, $getDocumentationResponse->statusText);

    }
}
