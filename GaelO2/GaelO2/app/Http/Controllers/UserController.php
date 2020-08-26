<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\GaelO\UseCases\CreateUser\CreateUser;
use App\GaelO\UseCases\CreateUser\CreateUserRequest;
use App\GaelO\UseCases\CreateUser\CreateUserResponse;

use App\GaelO\UseCases\ModifyUser\ModifyUser;
use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserResponse;

use App\GaelO\UseCases\GetUser\GetUser;
use App\GaelO\UseCases\GetUser\GetUserRequest;
use App\GaelO\UseCases\GetUser\GetUserResponse;

use App\GaelO\UseCases\ChangePassword\ChangePassword;
use App\GaelO\UseCases\ChangePassword\ChangePasswordRequest;
use App\GaelO\UseCases\ChangePassword\ChangePasswordResponse;

use App\GaelO\UseCases\DeleteUser\DeleteUser;
use App\GaelO\UseCases\DeleteUser\DeleteUserRequest;
use App\GaelO\UseCases\DeleteUser\DeleteUserResponse;

use App\GaelO\Util;

class UserController extends Controller
{

    public function getUser(int $id=0, GetUserRequest $getUserRequest, GetUserResponse $getUserResponse, GetUser $getUser) {
        $getUserRequest->id = $id;
        $getUser->execute($getUserRequest, $getUserResponse);
        return response()->json($getUserResponse->body, $getUserResponse->status);
    }

    public function createUser(Request $request, CreateUserRequest $createUserRequest, CreateUserResponse $createUserResponse, CreateUser $createUser) {
        $requestData = $request->all();
        $createUserRequest = Util::fillObject($requestData, $createUserRequest);
        $createUser->execute($createUserRequest, $createUserResponse);
        //SK Cette reponse a generaliser
        return response()
            ->json($createUserResponse->body)
            ->setStatusCode($createUserResponse->status, $createUserResponse->statusText);
    }

    public function modifyUser(int $id, Request $request, ModifyUserRequest $modifyUserRequest, ModifyUserResponse $modifyUserResponse, ModifyUser $modifyUser) {
        $requestData = $request->all();
        $requestData['id'] = $id;
        $modifyUserRequest = Util::fillObject($requestData, $modifyUserRequest);
        $modifyUser->execute($modifyUserRequest, $modifyUserResponse);
        return response()
            ->json($modifyUserResponse->body)
            ->setStatusCode($modifyUserResponse->status, $modifyUserResponse->statusText);
    }

    public function changeUserPassword(Request $request, ChangePasswordRequest $changePasswordRequest, ChangePasswordResponse $changePasswordResponse, ChangePassword $changePassword) {
        $requestData = $request->all();
        $changePasswordRequest = Util::fillObject($requestData, $changePasswordRequest);
        $changePassword->execute($changePasswordRequest, $changePasswordResponse);
        return response()->json($changePasswordResponse->body, $changePasswordResponse->status);
    }

    public function deleteUser(int $id, Request $request, DeleteUserRequest $deleteUserRequest, DeleteUserResponse $deleteUserResponse, DeleteUser $deleteUser) {
        $requestData = get_object_vars($request);
        $deleteUserRequest->id = $id;
        $deleteUserRequest = Util::fillObject($requestData, $deleteUserRequest);
        $deleteUser->execute($deleteUserRequest, $deleteUserResponse);
        return response()->json($deleteUserResponse->body, $deleteUserResponse->status);
    }

}
