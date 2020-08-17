<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\GaelO\Login\LoginRequest;
use App\GaelO\Login\LoginResponse;
use App\GaelO\Login\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;
use App\GaelO\UseCases\CreateUser\CreateUserRequest;
use App\GaelO\UseCases\CreateUser\CreateUserResponse;
use App\GaelO\UseCases\CreateUser\CreateUser;
use App;
use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserResponse;
use App\GaelO\UseCases\ModifyUser\ModifyUser;
use App\GaelO\UseCases\GetUser\GetUserRequest;
use App\GaelO\UseCases\GetUser\GetUserResponse;
use App\GaelO\UseCases\GetUser\GetUser;
use App\GaelO\UseCases\ChangePassword\ChangePasswordRequest;
use App\GaelO\UseCases\ChangePassword\ChangePasswordResponse;
use App\GaelO\UseCases\ChangePassword\ChangePassword;

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

    /** 
     * details api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function details()
    {
        $user = Auth::user();
        return response()->json(['success' => $user], $this->successStatus);
    }

    //Controlleur implementant la clean architecture
    public function loginClean(Request $request){
        //On recupere les infos qui nous interesse a partir du framework
        $requestData = $request->all();
        //On cree un object LoginRequest et un object LoginResponse
        //LoginRequest est un DTO qui contient les information qui nous interessent du framework
        //LoginResponse est la reponse de notre hexagone, c'est aussi un DTO, il suffit de le mettre dans response()->json() pour emmetre la reponse
        //La methode execute rempli l'object LoginResponse avec la logique metier ad hoc (fait dans l'hexagone)
        //Du coup dans l'hexagone il faudra faire des interface pour tous les appels aux fonctions du framework (appel db, email, log)
        //Par rapport à la video j'ai enleve la partie "Presenter" vue qu'on a plus de vue coté back, on a que du JSON et comme je suis obligé 
        //de return le response().json() depuis le controller je laisse la partie "presenter" dans le controller
        $loginRequest = new LoginRequest();
        $loginRequest->username = $requestData['username'];
        $loginRequest->password = $requestData['password'];
        $loginResponse = new LoginResponse();
        $login = new Login();
        $login->execute($loginRequest, $loginResponse);
        return response()->json($loginResponse);
    }

    public function getUser($id=0, GetUserRequest $getUserRequest, GetUserResponse $getUserResponse) {
        $getUserRequest->id = $id;
        $getUser = App::make('GetUser');
        $getUser->execute($getUserRequest, $getUserResponse);
        return response()->json($getUserResponse);
    }

    public function createUser(Request $request, CreateUserRequest $createUserRequest, CreateUserResponse $createUserResponse) {
        $requestData = $request->all();

        foreach($requestData as $property => $value) {
            $createUserRequest->$property = isset($requestData[$property]) ? $requestData[$property] : null;
        } 
        $createUser = App::make('CreateUser');
        $createUser->execute($createUserRequest, $createUserResponse);
        return response()->json($createUserResponse, 201);

    }

    public function modifyUser(Request $request, ModifyUserRequest $modifyUserRequest, ModifyUserResponse $modifyUserResponse) {
        $requestData = $request->all();

        $modifyUserRequestData = get_object_vars($modifyUserRequest);
        foreach($modifyUserRequestData as $property => $value) {
            $modifyUserRequest->$property = isset($requestData[$property]) ? $requestData[$property] : null;
        }
        $modifyUser = App::make('ModifyUser');
        $modifyUser->execute($modifyUserRequest, $modifyUserResponse);
        return response()->json($modifyUserResponse, 200);
    }

    public function changeUserPassword(Request $request, ChangePasswordRequest $changePasswordRequest, ChangePasswordResponse $changePasswordResponse) {
        $requestData = $request->all();
        
        $changePasswordRequest->username = $request['username'];
        $changePasswordRequest->password1 = $request['password1'];
        $changePasswordRequest->password2 = $request['password2'];
        $changePasswordRequest->previous_password = $request['previous_password'];
        $changePassword = App::make('ChangePassword');
        $changePassword->execute($changePasswordRequest, $changePasswordResponse);
        return response()->json($changePasswordResponse, );
    }
}
