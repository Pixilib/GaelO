<?php

namespace App\GaelO\Entities;

class UserEntity
{
    public int $id;
    public ?String $lastname;
    public ?String $firstname;
    public String $email;
    public ?String $phone;
    public ?String $lastPasswordUpdate;
    public String $attempts;
    public bool $administrator;
    public int $centerCode;
    public String $job;
    public ?String $orthancAddress;
    public ?String $orthancLogin;
    public ?String $deletedAt;
    public ?String $onboardingVersion;
    public ?string $emailVerifiedAt;
    public ?string $lastConnection;
    
    public CenterEntity $mainCenter;
    public array $affiliatedCenters;

    public ?array $roles;

    public static function fillFromDBReponseArray(array $array): UserEntity
    {
        $userEntity  = new UserEntity();
        $userEntity->id = $array['id'];
        $userEntity->lastname = $array['lastname'];
        $userEntity->firstname = $array['firstname'];
        $userEntity->email = $array['email'];
        $userEntity->phone = $array['phone'];
        $userEntity->attempts = $array['attempts'];
        $userEntity->administrator = $array['administrator'];
        $userEntity->centerCode = $array['center_code'];
        $userEntity->job = $array['job'];
        $userEntity->orthancAddress = $array['orthanc_address'];
        $userEntity->orthancLogin = $array['orthanc_login'];
        $userEntity->deletedAt = $array['deleted_at'];
        $userEntity->emailVerifiedAt = $array['email_verified_at'];
        $userEntity->lastConnection = $array['last_connection'];
        $userEntity->onboardingVersion = $array['onboarding_version'];
        return $userEntity;
    }

    public static function fillMinimalFromDBReponseArray(array $array): UserEntity
    {
        $userEntity  = new UserEntity();
        $userEntity->id = $array['id'];
        $userEntity->firstname = $array['firstname'];
        $userEntity->lastname = $array['lastname'];
        $userEntity->centerCode = $array['center_code'];
        $userEntity->email = $array['email'];
        $userEntity->phone = $array['phone'];
        return $userEntity;
    }

    public static function fillOnlyUserIdentification(array $user): UserEntity
    {
        $userEntity = new UserEntity();
        $userEntity->lastname = $user['lastname'];
        $userEntity->firstname = $user['firstname'];
        $userEntity->centerCode = $user['center_code'];
        $userEntity->email = $user['email'];
        return $userEntity;
    }

    public function addRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function setMainCenter(CenterEntity $mainCenter) :void 
    {
        $this->mainCenter = $mainCenter;
    }

    public function setAffiliatedCenters(array $affiliatedCenters) :void
    {
        $this->affiliatedCenters = $affiliatedCenters;
    }
}
