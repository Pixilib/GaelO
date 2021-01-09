<?php

namespace Tests;

use App\Models\Role;
use Laravel\Passport\Passport;
use App\Models\User;
use Illuminate\Support\Carbon;

class AuthorizationTools {

    public static function addRoleToUser(int $userId, string $role, string $studyName){
        factory(Role::class, 1)->create(
            ['name'=> $role,
            'user_id' => $userId,
            'study_name'=> $studyName]
        );
    }

    public static function actAsAdmin(bool $admin){
        $user = factory(User::class)->create(['administrator'=>$admin, 'status'=>'Activated', 'last_password_update'=> Carbon::now()->format('Y-m-d H:i:s')]);

        Passport::actingAs(
            User::where('id', $user->id)->first()
        );

    }
}
