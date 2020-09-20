<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\User;
use App\GaelO\Constants\Constants;
use Illuminate\Support\Facades\Artisan;

class Login extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp() : void{
        parent::setUp();
        Artisan::call('passport:install');
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
        $this->json('POST', '/api/login', $data)->assertNoContent(401);
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
        $this->json('POST', '/api/login', $data)->assertNoContent(433);
        //Try with correct temporary password, should grant access of unconfirmed status
        $data = ['username'=> 'administrator',
        'password'=> 'tempPassword'];
        $this->json('POST', '/api/login', $data)->assertNoContent(432);
    }

    public function testLoginPasswordPerished()
    {
        $data = ['username'=> 'administrator',
        'password'=> 'administrator'];
        $this->json('POST', '/api/login', $data)->assertNoContent(435);
    }

    public function testAccountBlocked(){
        //Access should be forbidden even if credential correct because of blocker status
        $data = ['username'=> 'administrator',
        'password'=> 'administrator'];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser['status'] = Constants::USER_STATUS_BLOCKED;
        $adminDefaultUser->save();
        $this->json('POST', '/api/login', $data)->assertNoContent(434);

    }

    public function testBlokingAccount(){
        // Three wrong attempts to login should block account
        $data = ['username'=> 'administrator',
        'password'=> 'wrongPassword'];

        $this->json('POST', '/api/login', $data)->assertNoContent(401);
        $this->json('POST', '/api/login', $data)->assertNoContent(401);
        $this->json('POST', '/api/login', $data)->assertNoContent(401);

        $data = ['username'=> 'administrator',
        'password'=> 'administrator'];
        $this->json('POST', '/api/login', $data)->assertNoContent(434);

        $adminDefaultUser = User::where('id', 1)->first();
        $this->assertEquals($adminDefaultUser['status'], Constants::USER_STATUS_BLOCKED);
        $this->assertEquals($adminDefaultUser['attempts'], 3);

    }
}
