<?php

namespace App\GaelO\UseCases\GetUser;

class UserEntity {

    public String $lastname;
    public String $firstName;
    public String $username;
    public String $email;
    public ?String $phone;
    public ?String $lastPasswordUpdate;
    public String $status;
    public String $attempts;
    public bool $administrator;
    public int $centerCode;
    public String $job;
    public ?String $orthancAddress;
    public ?String $orthancLogin;
    public ?String $orthancPassword;
    public ?String $deletedAt;

    public static function fillFromDBReponseArray(array $array){
        $userEntity  = new UserEntity();
        $userEntity->lastname = $array['lastname'];
        $userEntity->firstname = $array['firstname'];
        $userEntity->username = $array['username'];
        $userEntity->email = $array['email'];
        $userEntity->phone = $array['phone'];
        $userEntity->lastPasswordUpdate = $array['last_password_update'];
        $userEntity->status = $array['status'];
        $userEntity->attempts = $array['attempts'];
        $userEntity->administrator = $array['administrator'];
        $userEntity->centerCode = $array['center_code'];
        $userEntity->job = $array['job'];
        $userEntity->orthancAddress = $array['orthanc_address'];
        $userEntity->orthancLogin = $array['orthanc_login'];
        $userEntity->orthancPassword = $array['orthanc_password'];
        $userEntity->deletedAt = $array['deleted_at'];

        return $userEntity;
    }

}
