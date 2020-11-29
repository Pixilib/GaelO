<?php

namespace Tests;

use App\Role;
use Laravel\Passport\Passport;
use App\User;

class AuthorizationTools {

    public static function addRoleToUser(int $userId, string $role, string $studyName){
        factory(Role::class, 1)->create(
            ['name'=> $role,
            'user_id' => $userId,
            'study_name'=> $studyName]
        );
    }

    public static function actAsAdmin(bool $admin){
        $user = factory(User::class)->create(['administrator'=>$admin]);
        Passport::actingAs(
            User::where('id',$user->id)->first()
        );

    }
}
