<?php

namespace App\GaelO\Entities;

class UserRoleEntity {
    public int $userId;
    public string $role;
    public string $studyName;

    public static function fillFromDBReponseArray(array $array){
        $userEntity  = new UserRoleEntity();
        $userEntity->userId = $array['user_id'];
        $userEntity->role = $array['role'];
        $userEntity->studyName = $array['study_name'];
        return $userEntity;
    }

}
