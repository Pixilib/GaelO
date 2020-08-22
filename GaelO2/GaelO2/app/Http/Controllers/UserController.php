<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\GaelO\UseCases\Login\LoginRequest;
use App\GaelO\UseCases\Login\LoginResponse;
use App\GaelO\UseCases\Login\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\GaelO\UseCases\CreateUser\CreateUserRequest;
use App\GaelO\UseCases\CreateUser\CreateUserResponse;
use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserResponse;

use App\GaelO\UseCases\GetUser\GetUserRequest;
use App\GaelO\UseCases\GetUser\GetUserResponse;
use App\GaelO\UseCases\ChangePassword\ChangePasswordRequest;
use App\GaelO\UseCases\ChangePassword\ChangePasswordResponse;
use App\GaelO\UseCases\DeleteUser\DeleteUserRequest;
use App\GaelO\UseCases\DeleteUser\DeleteUserResponse;
use App\GaelO\Util;
use App;
use App\GaelO\UseCases\CreateUser\CreateUser;
use App\GaelO\UseCases\GetUser\GetUser;

use Illuminate\Support\Facades\Mail;
use App\Mail\UserCreated;

class UserController extends Controller
{
    public $successStatus = 200;
    /**
     * login api (exposed at /api/login)
     *
     * @return \Illuminate\Http\Response
     */
    public function login()
    {
        if (Auth::attempt(['username' => request('username'), 'password' => request('password')])) {
            $user = Auth::user();
            $success['token'] =  $user->createToken('MyApp')->accessToken;
            return response()->json(['success' => $success], $this->successStatus);
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }

    //Controlleur implementant la clean architecture
    public function loginClean(Request $request){
        $requestData = $request->all();
        $loginRequest = new LoginRequest();
        $loginRequest->username = $requestData['username'];
        $loginRequest->password = $requestData['password'];
        $loginResponse = new LoginResponse();
        $login = new Login();
        $login->execute($loginRequest, $loginResponse);
        return response()->json($loginResponse->body, $loginResponse->status);
    }

    public function getUser(int $id=0, GetUserRequest $getUserRequest, GetUserResponse $getUserResponse, GetUser $getUser) {
        $getUserRequest->id = $id;
        $getUser->execute($getUserRequest, $getUserResponse);
        return response()->json($getUserResponse->body, $getUserResponse->status);
    }

    public function createUser(Request $request, CreateUserRequest $createUserRequest, CreateUserResponse $createUserResponse, CreateUser $createUser) {
        $requestData = $request->all();
        $createUserRequest = Util::fillObject($requestData, $createUserRequest);
        $createUser->execute($createUserRequest, $createUserResponse);
        return response()->json($createUserResponse->body, $createUserResponse->status);
    }

    public function modifyUser(int $id, Request $request, ModifyUserRequest $modifyUserRequest, ModifyUserResponse $modifyUserResponse) {
        $requestData = $request->all();
        $requestData['id'] = $id;
        $modifyUserRequest = Util::fillObject($requestData, $modifyUserRequest);
        $modifyUser = App::make('ModifyUser');
        $modifyUser->execute($modifyUserRequest, $modifyUserResponse);
        return response()->json($modifyUserResponse->body, $modifyUserResponse->status);
    }

    public function changeUserPassword(Request $request, ChangePasswordRequest $changePasswordRequest, ChangePasswordResponse $changePasswordResponse) {
        $requestData = $request->all();
        $changePasswordRequest = Util::fillObject($requestData, $changePasswordRequest);
        $changePassword = App::make('ChangePassword');
        $changePassword->execute($changePasswordRequest, $changePasswordResponse);
        return response()->json($changePasswordResponse->body, $changePasswordResponse->status);
    }

    public function deleteUser(int $id, Request $request, DeleteUserRequest $deleteUserRequest, DeleteUserResponse $deleteUserResponse) {
        $requestData = get_object_vars($request);
        $deleteUserRequest->id = $id;
        $deleteUserRequest = Util::fillObject($requestData, $deleteUserRequest);
        $deleteUser = App::make('DeleteUser');
        $deleteUser->execute($deleteUserRequest, $deleteUserResponse);
        return response()->json($deleteUserResponse->body, $deleteUserResponse->status);
    }

    public function testMail(){
        Mail::to('salim.kanoun@gmail.com')->queue(new UserCreated());
        return response()->json(true);
    }
}
