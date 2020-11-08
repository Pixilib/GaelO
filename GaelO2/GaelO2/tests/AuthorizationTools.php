<?php

namespace Tests;

use App\Role;

class AuthorizationTools {

    public static function addRoleToUser(int $userId, string $role, string $studyName){
        factory(Role::class, 1)->create(
            ['name'=> $role,
            'user_id' => $userId,
            'study_name'=> $studyName]
        );
    }
}
