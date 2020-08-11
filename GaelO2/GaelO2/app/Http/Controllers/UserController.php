<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\GaelO\Login\LoginRequest;
use App\GaelO\Login\LoginResponse;
use App\GaelO\Login\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;

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

    public function getUser() {
        $user = User::find(1);
        $roles=$user->roles;
        return response()->json($roles);
    }
}
