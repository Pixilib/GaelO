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
        $adminDefaultUser->onboarding_version = Config::get('app.onboarding_version');
        $adminDefaultUser->save();
        $response = $this->json('POST', '/api/login', $data)->assertSuccessful();
        $content = json_decode($response->content(), true);
        $this->assertArrayHasKey('access_token', $content);
        $this->assertEquals($content['onboarded'], true);
    }

    public function testLoginShouldPassInsensitive()
    {
        $data = [
            'email' => strtoupper('administrator@gaelo.fr'),
            'password' => 'administrator'
        ];
        $adminDefaultUser = User::where('id', 1)->first();
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
