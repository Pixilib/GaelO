<?php

namespace App\GaelO\Login;

interface Executable {

    public function execute(LoginRequest $request, LoginResponse $outpout) : void;

}