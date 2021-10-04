<?php

namespace Tests\Feature\TestUser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;
use App\GaelO\Constants\Constants;
use Tests\AuthorizationTools;

class LoginTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    /**
     * Test login with correct username password and valid account (password up to date)
     */
    public function testLogin()
    {
        $data = ['username'=> 'administrator',
        'password'=> 'administrator'];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser['last_password_update'] = now();
        $adminDefaultUser->save();
        $response = $this->json('POST', '/api/login', $data)-> assertSuccessful();
        $content= json_decode($response->content(), true);
        $this->assertArrayHasKey('access_token', $content);
    }

    public function testLoginWrongPassword()
    {
        $data = ['username'=> 'administrator',
        'password'=> 'wrongPassword'];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser['last_password_update'] = now();
        $adminDefaultUser->save();
        $this->json('POST', '/api/login', $data)->assertStatus(401);
    }

    public function testLoginShouldFailBecauseUnconfirmedAccound()
    {
        //Try with correct main password but user in unconfirmed status, should fail
        $data = ['username'=> 'administrator',
        'password'=> 'administrator'];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser['status'] = Constants::USER_STATUS_UNCONFIRMED;
        $adminDefaultUser['password_temporary'] = Hash::make('tempPassword');
        $adminDefaultUser->save();
        $this->json('POST', '/api/login', $data)->assertStatus(401);
        //Try with correct temporary password, should grant access of unconfirmed status
        $data = ['username'=> 'administrator',
        'password'=> 'tempPassword'];
        $response = $this->json('POST', '/api/login', $data)->assertStatus(400);
        $content = $response->content();
        $responseArray = json_decode($content, true);
        $this->assertEquals(1, $responseArray['id']);
    }

    public function testLoginPasswordPerished()
    {
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser['last_password_update'] = date_create('10 June 2020');
        $adminDefaultUser->save();
        $data = ['username'=> 'administrator',
        'password'=> 'administrator'];
        $response = $this->json('POST', '/api/login', $data)->assertStatus(400);
        $content = $response->content();
        $responseArray = json_decode($content, true);
        $this->assertEquals(1, $responseArray['id']);
    }

    public function testAccountBlocked(){
        //Access should be forbidden even if credential correct because of blocker status
        $data = ['username'=> 'administrator',
        'password'=> 'administrator'];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser['status'] = Constants::USER_STATUS_BLOCKED;
        $adminDefaultUser->save();
        $this->json('POST', '/api/login', $data)->assertStatus(400);

    }

    public function testBlokingAccount(){
        // Three wrong attempts to login should block account
        $data = ['username'=> 'administrator',
        'password'=> 'wrongPassword'];

        $this->json('POST', '/api/login', $data)->assertStatus(401);
        $this->json('POST', '/api/login', $data)->assertStatus(401);
        $this->json('POST', '/api/login', $data)->assertStatus(401);

        $adminDefaultUser = User::where('id', 1)->first();
        $this->assertEquals($adminDefaultUser['status'], Constants::USER_STATUS_BLOCKED);
        $this->assertEquals($adminDefaultUser['attempts'], 3);

    }

    public function testBlockingUnconfirmedAccount(){
        // Three wrong attempts to login should block unconfirmed account
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser['status'] = Constants::USER_STATUS_UNCONFIRMED;
        $adminDefaultUser['password'] = null;
        $adminDefaultUser['password_temporary'] = 'password';
        $adminDefaultUser->save();

        $data = ['username'=> 'administrator',
        'password'=> 'wrongPassword'];

        $this->json('POST', '/api/login', $data)->assertStatus(401);
        $this->json('POST', '/api/login', $data)->assertStatus(401);
        $this->json('POST', '/api/login', $data)->assertStatus(401);

        $adminDefaultUser = User::where('id', 1)->first();
        $this->assertEquals($adminDefaultUser['status'], Constants::USER_STATUS_BLOCKED);
        $this->assertEquals($adminDefaultUser['attempts'], 3);
    }

    public function testRefreshToken(){
        AuthorizationTools::actAsAdmin(false);
        $answer = $this->json('GET', '/api/login/refreshToken');
        $this->assertArrayHasKey('access_token', json_decode($answer->content(), true) );
    }

    public function testRefreshTokenShouldFailNotIdentified(){
        $this->json('GET', '/api/login/refreshToken')->assertStatus(401);
    }
}
