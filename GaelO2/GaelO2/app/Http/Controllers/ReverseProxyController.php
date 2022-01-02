<?php

namespace App\Http\Controllers;

use App\GaelO\UseCases\ReverseProxyDicomWeb\ReverseProxyDicomWeb;
use App\GaelO\UseCases\ReverseProxyDicomWeb\ReverseProxyDicomWebRequest;
use App\GaelO\UseCases\ReverseProxyDicomWeb\ReverseProxyDicomWebResponse;
use App\GaelO\UseCases\ReverseProxyTus\ReverseProxyTus;
use App\GaelO\UseCases\ReverseProxyTus\ReverseProxyTusRequest;
use App\GaelO\UseCases\ReverseProxyTus\ReverseProxyTusResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReverseProxyController extends Controller
{
    public function tusUpload(Request $request, ReverseProxyTus $reverseProxyTus, ReverseProxyTusRequest $reverseProxyTusRequest, ReverseProxyTusResponse $reverseProxyTusResponse, string $filename=null){
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
        $reverseProxyDicomWebRequest->method =$request->method();
        $reverseProxyDicomWebRequest->body =$request->getContent();

        $reverseProxyDicomWeb->execute($reverseProxyDicomWebRequest, $reverseProxyDicomWebResponse);
        if($reverseProxyDicomWebResponse->status === 200){
            return response($reverseProxyDicomWebResponse->body, $reverseProxyDicomWebResponse->status , $reverseProxyDicomWebResponse->header);
        }else{
            return response()->json($reverseProxyDicomWebResponse->body)
                ->setStatusCode($reverseProxyDicomWebResponse->status, $reverseProxyDicomWebResponse->statusText);
        }


    }
}
