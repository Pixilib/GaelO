<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\CreateDocumentation\CreateDocumentation;
use App\GaelO\UseCases\CreateDocumentation\CreateDocumentationRequest;
use App\GaelO\UseCases\CreateDocumentation\CreateDocumentationResponse;
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
}
