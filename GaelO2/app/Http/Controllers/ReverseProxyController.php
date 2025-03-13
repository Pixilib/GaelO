<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\ReverseProxyDicomWeb\ReverseProxyDicomWeb;
use App\GaelO\UseCases\ReverseProxyDicomWeb\ReverseProxyDicomWebRequest;
use App\GaelO\UseCases\ReverseProxyDicomWeb\ReverseProxyDicomWebResponse;
use App\GaelO\UseCases\ReverseProxyTus\ReverseProxyTus;
use App\GaelO\UseCases\ReverseProxyTus\ReverseProxyTusRequest;
use App\GaelO\UseCases\ReverseProxyTus\ReverseProxyTusResponse;
use App\GaelO\UseCases\ReverseProxyWsi\ReverseProxyWsi;
use App\GaelO\UseCases\ReverseProxyWsi\ReverseProxyWsiRequest;
use App\GaelO\UseCases\ReverseProxyWsi\ReverseProxyWsiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReverseProxyController extends Controller
{
    public function tusUpload(Request $request, ReverseProxyTus $reverseProxyTus, ReverseProxyTusRequest $reverseProxyTusRequest, ReverseProxyTusResponse $reverseProxyTusResponse){
        $currentUser = Auth::user();
        $reverseProxyTusRequest->currentUserId = $currentUser['id'];

        $reverseProxyTusRequest->header =$request->header();
        $reverseProxyTusRequest->url =$request->getRequestUri();
        $reverseProxyTusRequest->method =$request->method();
        $reverseProxyTusRequest->body =$request->getContent();

        $reverseProxyTus->execute($reverseProxyTusRequest, $reverseProxyTusResponse);

        return response($reverseProxyTusResponse->body, $reverseProxyTusResponse->status , $reverseProxyTusResponse->header);
    }

    public function dicomWebReverseProxy(Request $request, ReverseProxyDicomWeb $reverseProxyDicomWeb, ReverseProxyDicomWebRequest $reverseProxyDicomWebRequest, ReverseProxyDicomWebResponse $reverseProxyDicomWebResponse){

        $currentUser = Auth::user();
        $reverseProxyDicomWebRequest->currentUserId = $currentUser['id'];

        $reverseProxyDicomWebRequest->header =$request->header();
        $reverseProxyDicomWebRequest->url =$request->getRequestUri();
        $reverseProxyDicomWebRequest->body =$request->getContent();

        $reverseProxyDicomWeb->execute($reverseProxyDicomWebRequest, $reverseProxyDicomWebResponse);
        if($reverseProxyDicomWebResponse->status === 200){
            return response($reverseProxyDicomWebResponse->body, $reverseProxyDicomWebResponse->status , $reverseProxyDicomWebResponse->header);
        }else{
            return response()->json($reverseProxyDicomWebResponse->body)
                ->setStatusCode($reverseProxyDicomWebResponse->status, $reverseProxyDicomWebResponse->statusText);
        }


    }

    public function dicomWebWsiProxy(Request $request, ReverseProxyWsi $reverseProxyWsi, ReverseProxyWsiRequest $reverseProxyWsiRequest, ReverseProxyWsiResponse $reverseProxyWsiResponse){
       
        $currentUser = Auth::user();
        $reverseProxyWsiRequest->currentUserId = $currentUser['id'];

        $reverseProxyWsiRequest->header =$request->header();
        $reverseProxyWsiRequest->url =$request->getRequestUri();
        $reverseProxyWsiRequest->body =$request->getContent();

        $reverseProxyWsi->execute($reverseProxyWsiRequest, $reverseProxyWsiResponse);
        if($reverseProxyWsiResponse->status === 200){
            return response($reverseProxyWsiResponse->body, $reverseProxyWsiResponse->status , $reverseProxyWsiResponse->header);
        }else{
            return response()->json($reverseProxyWsiResponse->body)
                ->setStatusCode($reverseProxyWsiResponse->status, $reverseProxyWsiResponse->statusText);
        }
    }
}
