<?php

namespace GaelO;

use GaelO\Login;

class LoginController {

    public function __construct(Login $login)
    {
        $this->login = $login;
    }

    public function execute()
    {
        error_log('ici');
    }

}