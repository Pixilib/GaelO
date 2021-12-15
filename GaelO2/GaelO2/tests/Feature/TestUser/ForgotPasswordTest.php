<?php

namespace Tests\Feature\TestUser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ForgotPasswordTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    public function testValidResetPassword()
    {
        $user = User::factory()->create();
        $this->expectsNotification($user, ResetPasswordNotification::class);

        $response = $this->post('api/tools/forgot-password', ['email' => $user->email]);
        $response->assertStatus(200);
    }

    public function testWrongEmailResetPassword(){
        $data = [
            'email' => 'administrator2@gaelo.fr'
        ];
        $this->json('POST', 'api/tools/forgot-password', $data)
        ->assertStatus(404);

    }

    public function testResetDeactivatedAccount(){

        $defaultUser = User::find(1);
        $defaultUser->delete();

        $data = [
            'email' => 'administrator@gaelo.fr'
        ];

        $this->json('POST', 'api/tools/forgot-password', $data)->assertStatus(400);

    }

    public function testUpdatePassword(){
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->post('api/tools/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function testUpdatePasswordShouldFailWrongToken(){
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->post('api/tools/reset-password', [
            'token' => $token.'wrong',
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);

        $response->assertStatus(400);
        //Password shall not be updated
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }

    public function testUpdatePasswordShouldFailWrongEmail(){
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->post('api/tools/reset-password', [
            'token' => $token,
            'email' => $user->email.'wrong',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);

        $response->assertStatus(400);
        //Password shall not be updated
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }


    public function testUpdatePasswordShouldFailWrongConfirmation(){
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->post('api/tools/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password'.'wrong'
        ]);

        $response->assertStatus(302);
        //Password shall not be updated
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }
}
