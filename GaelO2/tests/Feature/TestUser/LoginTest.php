<?php

namespace Tests\Feature\TestUser;

use Illuminate\Foundation\Testing\DatabaseMigrations;;
use Tests\TestCase;
use App\Models\User;

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
     * Test login with correct email password and valid account (password up to date)
     */
    public function testLogin()
    {
        $data = ['email'=> 'administrator@gaelo.fr',
        'password'=> 'administrator'];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser->save();
        $response = $this->json('POST', '/api/login', $data)-> assertSuccessful();
        $content= json_decode($response->content(), true);
        $this->assertArrayHasKey('access_token', $content);
    }


    public function testLoginNonExistingUser()
    {
        $data = ['email'=> 'administrator2@gaelo.fr',
        'password'=> 'administrator'];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser->save();
        $response = $this->json('POST', '/api/login', $data)->assertStatus(401);
    }

    public function testLoginWrongPassword()
    {
        $data = ['email'=> 'administrator@gaelo.fr',
        'password'=> 'wrongPassword'];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser->save();
        $this->json('POST', '/api/login', $data)->assertStatus(401);
    }

    public function testLoginShouldFailBecauseUnconfirmedAccound()
    {
        //Try with correct main password but user in unconfirmed status, should fail
        $data = ['email'=> 'administrator@gaelo.fr',
        'password'=> 'administrator'];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser['email_verified_at'] = null;
        $adminDefaultUser->save();
        $this->json('POST', '/api/login', $data)->assertStatus(401);
    }

    public function testAccountBlocked(){
        //Access should be forbidden even if credential correct because of blocker status
        $data = ['email'=> 'administrator@gaelo.fr',
        'password'=> 'administrator'];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser['attempts'] = 3;
        $adminDefaultUser->save();
        $this->json('POST', '/api/login', $data)->assertStatus(401);

    }

    public function testBlokingAccount(){
        // Three wrong attempts to login should block account
        $data = ['email'=> 'administrator@gaelo.fr',
        'password'=> 'wrongPassword'];

        $this->json('POST', '/api/login', $data)->assertStatus(401);
        $this->json('POST', '/api/login', $data)->assertStatus(401);
        $this->json('POST', '/api/login', $data)->assertStatus(401);

        $adminDefaultUser = User::where('id', 1)->first();
        $this->assertEquals($adminDefaultUser['attempts'], 3);

    }

}
