<?php

namespace App\GaelO\CreateUser;

class CreateUserRequest {
    public string $lastname ;
    public string $firstname ;
    public string $username ;
    public string $email ;
    public string $password ;
    public string $phone ;
    public string $status;
    public boolean $administrator;
    public unsignedInteger $center_code ;
    public string $job;
    public string $orthanc_address;
    public string $orthanc_login;
    public string $orthanc_password;
}