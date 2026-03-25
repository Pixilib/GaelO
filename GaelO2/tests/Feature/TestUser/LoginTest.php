<?php

namespace Tests\Feature\TestUser;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\TrackerRepository;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Tests\AuthorizationTools;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private MockInterface $trackerSpy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
    }

    /**
     * Test login with correct email password and valid account (password up to date)
     */
    public function testLogin()
    {
        $data = [
            'email' => 'administrator@gaelo.fr',
            'password' => 'administrator'
        ];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser->administrator = false;
        $adminDefaultUser->onboarding_version = Config::get('app.onboarding_version');
        $adminDefaultUser->save();
        $response = $this->json('POST', '/api/login', $data)->assertSuccessful();
        $content = json_decode($response->content(), true);
        $this->assertArrayHasKey('access_token', $content);
        $this->assertEquals($content['onboarded'], true);
    }

    public function testLoginAdministratorWith2FA()
    {
        //Ajout pour simuler une connexion normal
        User::where('email', 'administrator@gaelo.fr')
            ->update([
                'two_factor_secret' => encrypt('BASE32SECRETKEY'),
                'two_factor_confirmed_at' => now()
            ]);
        // Login doit retourner twoFA=true et challenge_token
        $response = $this->json('POST', '/api/login', [
            'email' => 'administrator@gaelo.fr',
            'password' => 'administrator'
        ]);

        $content = json_decode($response->content(), true);

        $this->assertTrue($content['needs2FA']);
        $this->assertArrayHasKey('challenge_token', $content);

        $challengeToken = $content['challenge_token'];

        // Mocker TwoFactorAuthenticationProvider
        // pour simuler un code TOTP valide sans vrai secret
        $this->mock(TwoFactorAuthenticationProvider::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn(true);
        });

        // Challenge 2FA avec le challenge_token récupéré
        $response = $this->json('POST', '/api/two-factor-challenge', [
            'challenge_token' => $challengeToken,
            'code' => '000000' // pas important car le provider est mocké
        ]);

        $content = json_decode($response->content(), true);

        $this->assertArrayHasKey('access_token', $content);
        $this->assertArrayHasKey('id', $content);
        $this->assertEquals('Bearer', $content['token_type']);
    }

    public function testLoginAdministratorWith2FARecoveryCode()
    {
        $recoveryCodes = ['aaaaaaaaaa-bbbbbbbbbb', 'cccccccccc-dddddddddd'];

        User::where('email', 'administrator@gaelo.fr')
            ->update([
                'two_factor_secret' => encrypt('BASE32SECRETKEY'),
                'two_factor_confirmed_at' => now(),
                'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes))
            ]);

        // Login → twoFA=true + challenge_token
        $response = $this->json('POST', '/api/login', [
            'email' => 'administrator@gaelo.fr',
            'password' => 'administrator'
        ]);

        $content = json_decode($response->content(), true);

        $this->assertTrue($content['needs2FA']);
        $this->assertArrayHasKey('challenge_token', $content);

        $challengeToken = $content['challenge_token'];


        $response = $this->json('POST', '/api/two-factor-challenge', [
            'challenge_token' => $challengeToken,
            'recovery_code' => 'aaaaaaaaaa-bbbbbbbbbb'
        ]);

        $content = json_decode($response->content(), true);

        $this->assertArrayHasKey('access_token', $content);
        $this->assertArrayHasKey('id', $content);
        $this->assertEquals('Bearer', $content['token_type']);
    }

    public function testRecoveryCodeGeneration2FA()
    {
        User::where('email', 'administrator@gaelo.fr')
            ->update([
                'two_factor_secret' => encrypt('BASE32SECRETKEY'),
                'two_factor_confirmed_at' => now(),
                'two_factor_recovery_codes' => encrypt(json_encode(
                    collect(range(1, 8))
                        ->map(fn() => \Illuminate\Support\Str::random(10) . '-' . \Illuminate\Support\Str::random(10))
                        ->all()
                ))
            ]);


        $response = $this->json('POST', '/api/login', [
            'email' => 'administrator@gaelo.fr',
            'password' => 'administrator'
        ]);

        $content = json_decode($response->content(), true);
        $challengeToken = $content['challenge_token'];


        $this->mock(TwoFactorAuthenticationProvider::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn(true);
        });

        $response = $this->json('POST', '/api/two-factor-challenge', [
            'challenge_token' => $challengeToken,
            'code' => '123456'
        ]);

        $content = json_decode($response->content(), true);
        $bearerToken = $content['access_token'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $bearerToken
        ])->json('POST', '/api/user/generate-recovery-codes');

        $content = json_decode($response->content(), true);

        $this->assertArrayHasKey('recoveryCodes', $content);
        $this->assertCount(8, $content['recoveryCodes']);

    }

    public function testRecoveryCodeIsConsumedAfterUse()
    {
        $recoveryCodes = ['aaaaaaaaaa-bbbbbbbbbb', 'cccccccccc-dddddddddd'];
 
        User::where('email', 'administrator@gaelo.fr')
            ->update([
                'two_factor_secret' => encrypt('BASE32SECRETKEY'),
                'two_factor_confirmed_at' => now(),
                'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes))
            ]);
 
        // Login → challenge_token
        $response = $this->json('POST', '/api/login', [
            'email' => 'administrator@gaelo.fr',
            'password' => 'administrator'
        ]);
        $content = json_decode($response->content(), true);
        $this->assertTrue($content['needs2FA']);
        $challengeToken = $content['challenge_token'];
 
        // Use of the first recovery code
        $response = $this->json('POST', '/api/two-factor-challenge', [
            'challenge_token' => $challengeToken,
            'recovery_code' => 'aaaaaaaaaa-bbbbbbbbbb'
        ]);
        $response->assertStatus(200);
        $content = json_decode($response->content(), true);
        $this->assertArrayHasKey('access_token', $content);

        $user = User::where('email', 'administrator@gaelo.fr')->first();
        $remainingCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
 
        $this->assertCount(1, $remainingCodes);
        $this->assertNotContains('aaaaaaaaaa-bbbbbbbbbb', $remainingCodes);
        $this->assertContains('cccccccccc-dddddddddd', $remainingCodes);
    }

    public function testSetup2FA()
    {
        // User initialization
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser->administrator = false;
        $adminDefaultUser->onboarding_version = Config::get('app.onboarding_version');
        $adminDefaultUser->save();

        $bearerToken = $adminDefaultUser->createToken('GaelO')->plainTextToken;

        // Mock 2FA provider 
        $this->mock(TwoFactorAuthenticationProvider::class, function ($mock) {
            $mock->shouldReceive('generateSecretKey')->once()->andReturn('BASE32SECRETKEY');
            $mock->shouldReceive('verify')->once()->andReturn(true);
        });

        // Initialization
        $response = $this->json('POST', '/api/user/two-factor-setup', [], [
            'Authorization' => 'Bearer ' . $bearerToken
        ]);

        $response->assertStatus(200);

        $content = $response->json();
        $this->assertArrayHasKey('svg', $content);
        $this->assertNotEmpty($content['svg']);

        $user = User::where('id', 1)->first()->fresh();
        $this->assertNotEmpty($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        // Confirmation
        $response = $this->json('POST', '/api/user/two-factor-setup/confirm', [
            'code' => '123456'
        ], [
            'Authorization' => 'Bearer ' . $bearerToken
        ]);

        $response->assertStatus(200);

        $content = $response->json();
        $this->assertEquals('2FA activated', $content['message']);

        $user = User::where('id', 1)->first()->fresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
    }

    public function testLoginShouldPassInsensitive()
    {
        $data = [
            'email' => strtoupper('administrator@gaelo.fr'),
            'password' => 'administrator'
        ];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser->administrator = false;
        $adminDefaultUser->onboarding_version = Config::get('app.onboarding_version');
        $adminDefaultUser->save();
        $response = $this->json('POST', '/api/login', $data)->assertSuccessful();
        $content = json_decode($response->content(), true);
        $this->assertArrayHasKey('access_token', $content);
        $this->assertEquals($content['onboarded'], true);
    }

    public function testLoginSuccessButNotOnboarded()
    {
        $data = [
            'email' => 'administrator@gaelo.fr',
            'password' => 'administrator'
        ];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser->onboarding_version = '0.0.0';
        $adminDefaultUser->administrator = false;
        $adminDefaultUser->save();
        $response = $this->json('POST', '/api/login', $data)->assertSuccessful();
        $content = json_decode($response->content(), true);
        $this->assertArrayHasKey('access_token', $content);
        $this->assertEquals($content['onboarded'], false);
    }


    public function testLoginNonExistingUser()
    {
        $data = [
            'email' => 'administrator2@gaelo.fr',
            'password' => 'administrator'
        ];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser->save();
        $this->json('POST', '/api/login', $data)->assertStatus(401);
    }

    public function testLoginWrongPassword()
    {
        $data = [
            'email' => 'administrator@gaelo.fr',
            'password' => 'wrongPassword'
        ];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser->save();
        $this->json('POST', '/api/login', $data)->assertStatus(401);
    }

    public function testLoginShouldFailBecauseUnconfirmedAccound()
    {
        //Try with correct main password but user in unconfirmed status, should fail
        $data = [
            'email' => 'administrator@gaelo.fr',
            'password' => 'administrator'
        ];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser['email_verified_at'] = null;
        $adminDefaultUser->save();
        $this->json('POST', '/api/login', $data)->assertStatus(401);
    }

    public function testAccountBlocked()
    {
        //Access should be forbidden even if credential correct because of blocker status
        $data = [
            'email' => 'administrator@gaelo.fr',
            'password' => 'administrator'
        ];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser['attempts'] = 3;
        $adminDefaultUser->save();
        $this->json('POST', '/api/login', $data)->assertStatus(401);
    }

    public function testBlokingAccount()
    {
        // Three wrong attempts to login should block account
        $data = [
            'email' => 'administrator@gaelo.fr',
            'password' => 'wrongPassword'
        ];

        $this->json('POST', '/api/login', $data)->assertStatus(401);
        $this->json('POST', '/api/login', $data)->assertStatus(401);
        $this->json('POST', '/api/login', $data)->assertStatus(401);

        $adminDefaultUser = User::where('id', 1)->first();
        $this->assertEquals($adminDefaultUser['attempts'], 3);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with(1, Constants::TRACKER_ROLE_USER, Mockery::any(), Mockery::any(), Constants::TRACKER_ACCOUNT_BLOCKED, Mockery::any());
    }

    public function testLogout()
    {
        AuthorizationTools::actAsAdmin(true);
        $this->json('DELETE', '/api/login')->assertSuccessful();
    }
}
