<?php

namespace Tests\Feature\TestUser;

use Tests\TestCase;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function testValidResetPassword()
    {
        $user = User::factory()->create();
        Notification::fake();

        $response = $this->post('api/tools/forgot-password', ['email' => $user->email]);
        $response->assertStatus(200);
        
        Notification::assertSentTo(
            [$user], ResetPasswordNotification::class
        );
        
    }

    public function testWrongEmailResetPassword()
    {
        $data = [
            'email' => 'administrator2@gaelo.fr'
        ];
        $this->json('POST', 'api/tools/forgot-password', $data)
            ->assertStatus(200);
    }

    public function testResetDeactivatedAccount()
    {

        $defaultUser = User::find(1);
        $defaultUser->delete();

        $data = [
            'email' => 'administrator@gaelo.fr'
        ];

        $this->json('POST', 'api/tools/forgot-password', $data)->assertStatus(400);
    }

    public function testUpdatePassword()
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $password = '>=5KBhxE=wWC';

        $response = $this->post('api/tools/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password
        ]);

        $this->assertTrue(Hash::check($password, $user->fresh()->password));
    }

    public function testUpdatePasswordShouldFailWrongToken()
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $password = '>=5KBhxE=wWC';

        $response = $this->post('api/tools/reset-password', [
            'token' => $token . 'wrong',
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password
        ]);

        $this->assertFalse(Hash::check($password, $user->fresh()->password));
    }

    public function testUpdatePasswordShouldFailWrongEmail()
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $password = '>=5KBhxE=wWC';

        $response = $this->post('api/tools/reset-password', [
            'token' => $token,
            'email' => $user->email . 'wrong',
            'password' => $password,
            'password_confirmation' => $password
        ]);

        $response->assertStatus(400);
        //Password shall not be updated
        $this->assertFalse(Hash::check($password, $user->fresh()->password));
    }


    public function testUpdatePasswordShouldFailWrongConfirmation()
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $password = '>=5KBhxE=wWC';

        $response = $this->post('api/tools/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password . 'wrong'
        ]);

        $response->assertInvalid('password');
        //Password shall not be updated
        $this->assertFalse(Hash::check($password, $user->fresh()->password));
    }

    public function testUpdatePasswordShouldFailNotMixedCase()
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $password = strtolower('>=5KBhxE=wWC');

        $response = $this->post('api/tools/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password
        ]);

        $response->assertInvalid('password');
        $this->assertFalse(Hash::check($password, $user->fresh()->password));
    }

    public function testUpdatePasswordShouldFailNotTwelveCharsLong()
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $password = '>=5KBhxE=wW';

        $response = $this->post('api/tools/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password
        ]);

        $response->assertInvalid('password');
        $this->assertFalse(Hash::check($password, $user->fresh()->password));
    }

    public function testUpdatePasswordShouldFailNoNumber()
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $password = '>=AKBhxE=wWC';

        $response = $this->post('api/tools/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password
        ]);

        $response->assertInvalid('password');
        $this->assertFalse(Hash::check($password, $user->fresh()->password));
    }

    public function testUpdatePasswordShouldFailCompromised()
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $password = 'Password123!';

        $response = $this->post('api/tools/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password
        ]);

        $response->assertInvalid('password');
        $this->assertFalse(Hash::check($password, $user->fresh()->password));
    }
}
