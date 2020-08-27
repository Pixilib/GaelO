<?php

namespace App\Http\Controllers;

use App\GaelO\Util;
use App\GaelO\UseCases\ResetPassword\ResetPassword;
use App\GaelO\UseCases\ResetPassword\ResetPasswordRequest;
use App\GaelO\UseCases\ResetPassword\ResetPasswordResponse;
use Illuminate\Http\Request;

class ToolsController extends Controller
{
    public function resetPassword(Request $request, ResetPasswordRequest $resetPasswordRequest, ResetPasswordResponse $resetPasswordResponse, ResetPassword $resetPassword){
        $requestData = $request->all();
        $requestRequest = Util::fillObject($requestData, $resetPasswordRequest);
        $resetPassword->execute($requestRequest, $resetPasswordResponse);
        return response()->json([])
                    ->setStatusCode($resetPasswordResponse->status, $resetPasswordResponse->statusText);
    }
}
