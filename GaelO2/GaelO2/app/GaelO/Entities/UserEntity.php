<?php

namespace App\GaelO\Entities;

class UserEntity {
    public int $id;
    public ?String $lastname;
    public ?String $firstname;
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

    public ?array $roles;

    public static function fillFromDBReponseArray(array $array){
        $userEntity  = new UserEntity();
        $userEntity->id = $array['id'];
        $userEntity->lastname = $array['lastname'];
        $userEntity->firstname = $array['firstname'];
        $userEntity->email = $array['email'];
        $userEntity->phone = $array['phone'];
        $userEntity->status = $array['status'];
        $userEntity->attempts = $array['attempts'];
        $userEntity->administrator = $array['administrator'];
        $userEntity->centerCode = $array['center_code'];
        $userEntity->job = $array['job'];
        $userEntity->orthancAddress = $array['orthanc_address'];
        $userEntity->orthancLogin = $array['orthanc_login'];
        $userEntity->orthancPassword = $array['orthanc_password'];
        $userEntity->deletedAt = $array['deleted_at'];
        $userEntity->lastConnection = $array['last_connection'];
        return $userEntity;
    }

    public static function fillMinimalFromDBReponseArray(array $array){
        $userEntity  = new UserEntity();
        $userEntity->id = $array['id'];
        $userEntity->firstname = $array['firstname'];
        $userEntity->lastname = $array['lastname'];
        return $userEntity;
    }

    public function addRoles(array $roles) : void {
        $this->roles = $roles;
    }
}
