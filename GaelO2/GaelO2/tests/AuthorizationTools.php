<?php

namespace Tests;

use App\GaelO\Constants\Constants;
use App\Models\CenterUser;
use App\Models\Role;
use Laravel\Passport\Passport;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

class AuthorizationTools {

    public static function addRoleToUser(int $userId, string $role, string $studyName){
        Role::factory()->userId($userId)->studyName($studyName)->roleName($role)->create();
    }

    public static function actAsAdmin(bool $admin) : int {

        Artisan::call('passport:install');

        if($admin){
            $user = User::factory()->administrator()->status(Constants::USER_STATUS_ACTIVATED)->create();
        }else{
            $user = User::factory()->status(Constants::USER_STATUS_ACTIVATED)->create();
        }

        Passport::actingAs(
            User::find($user->id)
        );
        return $user->id;
    }

    public static function addAffiliatedCenter(int $userId, int $centerCode){
        CenterUser::factory()->userId($userId)->centerCode($centerCode)->create();
    }
}
