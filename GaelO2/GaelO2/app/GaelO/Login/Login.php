<?php

namespace App\GaelO\Login;

use App\GaelO\Login\LoginRequest;
use App\GaelO\Login\LoginResponse;

class Login {

    public function execute(LoginRequest $loginRequest, LoginResponse $loginResponse) : void
    {   
        //Données sont dans l'hexagone via loginRequest
        //ici on met la logique et modifie l'object loginResponse
        $username = $loginRequest->username;
        if($username == true) $loginResponse->success = true;
    }

}