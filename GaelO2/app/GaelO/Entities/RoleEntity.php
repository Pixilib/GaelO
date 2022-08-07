<?php

namespace App\GaelO\Entities;

class RoleEntity
{
    public string $studyName;
    public string $name;
    public string $validatedDocumentationVersion;

    public static function fillFromDBReponseArray(array $array): RoleEntity
    {
        $roleEntity  = new RoleEntity();
        $roleEntity->studyName = $array['study_name'];
        $roleEntity->name = $array['name'];
        $roleEntity->validatedDocumentationVersion = $array['validated_documentation_version'];

        return $roleEntity;
    }
}
