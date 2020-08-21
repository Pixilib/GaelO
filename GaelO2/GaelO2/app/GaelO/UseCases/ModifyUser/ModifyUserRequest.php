<?php

namespace App\GaelO\UseCases\ModifyUser;

class ModifyUserRequest {
    public string $lastname ;
    public string $firstname ;
    public string $username ;
    public string $email ;
    public string $phone ;
    public string $status;
    public bool $administrator;
    public int $center_code ;
    public string $job;
    public string $orthanc_address;
    public string $orthanc_login;
    public string $orthanc_password;
}
