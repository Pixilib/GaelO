<?php

namespace App\Http\Controllers;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\UseCases\CreateDocumentation\CreateDocumentation;
use App\GaelO\UseCases\CreateDocumentation\CreateDocumentationRequest;
use App\GaelO\UseCases\CreateDocumentation\CreateDocumentationResponse;
use App\GaelO\UseCases\CreateDocumentationFile\CreateDocumentationFile;
use App\GaelO\UseCases\CreateDocumentationFile\CreateDocumentationFileRequest;
use App\GaelO\UseCases\CreateDocumentationFile\CreateDocumentationFileResponse;
use App\GaelO\UseCases\DeleteDocumentation\DeleteDocumentation;
use App\GaelO\UseCases\DeleteDocumentation\DeleteDocumentationRequest;
use App\GaelO\UseCases\DeleteDocumentation\DeleteDocumentationResponse;
use App\GaelO\UseCases\GetDocumentation\GetDocumentation;
use App\GaelO\UseCases\GetDocumentation\GetDocumentationRequest;
use App\GaelO\UseCases\GetDocumentation\GetDocumentationResponse;
use App\GaelO\UseCases\GetDocumentationFile\GetDocumentationFile;
use App\GaelO\UseCases\GetDocumentationFile\GetDocumentationFileRequest;
use App\GaelO\UseCases\GetDocumentationFile\GetDocumentationFileResponse;
use App\GaelO\UseCases\ModifyDocumentation\ModifyDocumentation;
use App\GaelO\UseCases\ModifyDocumentation\ModifyDocumentationRequest;
use App\GaelO\UseCases\ModifyDocumentation\ModifyDocumentationResponse;
use App\GaelO\UseCases\ReactivateDocumentation\ReactivateDocumentation;
use App\GaelO\UseCases\ReactivateDocumentation\ReactivateDocumentationRequest;
use App\GaelO\UseCases\ReactivateDocumentation\ReactivateDocumentationResponse;
use App\GaelO\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DocumentationController extends Controller
{
    public function createDocumentation(Request $request, CreateDocumentation $createDocumentation, CreateDocumentationRequest $createDocumentationRequest, CreateDocumentationResponse $createDocumentationResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();
        $createDocumentationRequest = Util::fillObject($requestData, $createDocumentationRequest);
        $createDocumentationRequest->currentUserId = $currentUser['id'];
        $createDocumentationRequest->studyName = $studyName;
        $createDocumentation->execute($createDocumentationRequest, $createDocumentationResponse);
        return $this->getJsonResponse($createDocumentationResponse->body, $createDocumentationResponse->status, $createDocumentationResponse->statusText);
    }

    public function uploadDocumentation(Request $request, CreateDocumentationFile $createDocumentationFile, CreateDocumentationFileRequest $createDocumentationFileRequest, CreateDocumentationFileResponse $createDocumentationFileResponse, int $documentationId)
    {
        $currentUser = Auth::user();
        $requestData = $request->getContent();
        $createDocumentationFileRequest->currentUserId = $currentUser['id'];
        $createDocumentationFileRequest->id = $documentationId;
        $createDocumentationFileRequest->contentType = $request->headers->get('Content-Type');
        $createDocumentationFileRequest->binaryData = $requestData;
        $createDocumentationFile->execute($createDocumentationFileRequest, $createDocumentationFileResponse);
        return $this->getJsonResponse($createDocumentationFileResponse->body, $createDocumentationFileResponse->status, $createDocumentationFileResponse->statusText);
    }

    public function deleteDocumentation(DeleteDocumentation $deleteDocumentation, DeleteDocumentationRequest $deleteDocumentationRequest, DeleteDocumentationResponse $deleteDocumentationResponse, int $documentationId)
    {
        $currentUser = Auth::user();
        $deleteDocumentationRequest->id = $documentationId;
        $deleteDocumentationRequest->currentUserId = $currentUser['id'];
        $deleteDocumentation->execute($deleteDocumentationRequest, $deleteDocumentationResponse);

        return $this->getJsonResponse($deleteDocumentationResponse->body, $deleteDocumentationResponse->status, $deleteDocumentationResponse->statusText);
    }

    public function getDocumentationsFromStudy(Request $request, GetDocumentation $getDocumentation, GetDocumentationRequest $getDocumentationRequest, GetDocumentationResponse $getDocumentationResponse, string $studyName)
    {
        $currentUser = Auth::user();
        $queryParam = $request->query();
        $getDocumentationRequest->role = $queryParam['role'];
        $getDocumentationRequest->studyName = $studyName;
        $getDocumentationRequest->currentUserId = $currentUser['id'];
        $getDocumentation->execute($getDocumentationRequest, $getDocumentationResponse);
        return $this->getJsonResponse($getDocumentationResponse->body, $getDocumentationResponse->status, $getDocumentationResponse->statusText);
    }

    public function getDocumentationFile(GetDocumentationFile $getDocumentationFile, GetDocumentationFileRequest $getDocumentationFileRequest, GetDocumentationFileResponse $getDocumentationFileResponse, int $documentationId)
    {
        $currentUser = Auth::user();
        $getDocumentationFileRequest->id = $documentationId;
        $getDocumentationFileRequest->currentUserId = $currentUser['id'];
        $getDocumentationFile->execute($getDocumentationFileRequest, $getDocumentationFileResponse);
        if ($getDocumentationFileResponse->status === 200) {
            FrameworkAdapter::getStoredFiles();
            return Storage::download($getDocumentationFileResponse->filePath, $getDocumentationFileResponse->filename);
        } else {
            return response()->json($getDocumentationFileResponse->body)
                ->setStatusCode($getDocumentationFileResponse->status, $getDocumentationFileResponse->statusText);
        }
    }

    public function modifyDocumentation(Request $request, ModifyDocumentation $modifyDocumentation, ModifyDocumentationRequest $modifyDocumentationRequest, ModifyDocumentationResponse $modifyDocumentationResponse, int $documentationId)
    {
        $currentUser = Auth::user();
        $requestData = $request->all();
        $modifyDocumentationRequest = Util::fillObject($requestData, $modifyDocumentationRequest);
        $modifyDocumentationRequest->id = $documentationId;
        $modifyDocumentationRequest->currentUserId = $currentUser['id'];
        $modifyDocumentation->execute($modifyDocumentationRequest, $modifyDocumentationResponse);
        return $this->getJsonResponse($modifyDocumentationResponse->body, $modifyDocumentationResponse->status, $modifyDocumentationResponse->statusText);
    }

    public function reactivateDocumentation(ReactivateDocumentation $reactivateDocumentation, ReactivateDocumentationRequest $reactivateDocumentationRequest, ReactivateDocumentationResponse $reactivateDocumentationResponse, int $documentationId){
        $currentUser = Auth::user();
        $reactivateDocumentationRequest->currentUserId = $currentUser['id'];
        $reactivateDocumentationRequest->documentationId = $documentationId;

        $reactivateDocumentation->execute($reactivateDocumentationRequest, $reactivateDocumentationResponse);

        return $this->getJsonResponse($reactivateDocumentationResponse->body, $reactivateDocumentationResponse->status, $reactivateDocumentationResponse->statusText);
    }
}
