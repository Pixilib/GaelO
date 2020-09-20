<?php

namespace App\GaelO\UseCases\GetUserRoles;

class RoleEntity {
    public string $name;
    public string $studyName;

    public static function fillFromDBReponseArray(array $array) : RoleEntity {
        $roleEntity  = new RoleEntity();
        $roleEntity->name = $array['name'];
        $roleEntity->studyName = $array['study_name'];
        return $roleEntity;
    }

}
