<?php

namespace Tests;

use App\Models\CenterUser;
use App\Models\Role;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AuthorizationTools {

    public static function addRoleToUser(int $userId, string $role, string $studyName){
        Role::factory()->userId($userId)->studyName($studyName)->roleName($role)->create();
    }

    public static function actAsAdmin(bool $admin) : int {

        if($admin){
            $user = User::factory()->administrator()->create();
        }else{
            $user = User::factory()->create();
        }

        Sanctum::actingAs(
            User::find($user->id)
        );
        return $user->id;
    }

    public static function addAffiliatedCenter(int $userId, int $centerCode){
        CenterUser::factory()->userId($userId)->centerCode($centerCode)->create();
    }

    public static function logAsUser(int $userId){
        Sanctum::actingAs(
            User::find($userId)
        );
    }
}
