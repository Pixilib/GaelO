<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\GetDicoms\GetDicoms;
use App\GaelO\UseCases\GetDicoms\GetDicomsRequest;
use App\GaelO\UseCases\GetDicoms\GetDicomsResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DicomController extends Controller
{


    public function getVisitDicoms(int $visitId = 0, Request $request, GetDicoms $getDicoms, GetDicomsRequest $getDicomsRequest, GetDicomsResponse $getDicomsResponse){
        $currentUser = Auth::user();
        $queryParam = $request->query();

        $getDicomsRequest->currentUserId = $currentUser['id'];
        $getDicomsRequest->visitId = $visitId;
        $getDicomsRequest->role = $queryParam['role'];
        $getDicoms->execute($getDicomsRequest, $getDicomsResponse);

        if($getDicomsResponse->status === 200) {

            return response()->streamDownload( function() use( &$getDicoms){
                $getDicoms->outputStream();
            }, $getDicomsResponse->filename);

        }else{
            return response()->json($getDicomsResponse->body)
            ->setStatusCode($getDicomsResponse->status, $getDicomsResponse->statusText);
        }


    }
}
