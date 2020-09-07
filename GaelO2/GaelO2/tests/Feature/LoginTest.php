<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\User;

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
        \Artisan::call('passport:install');
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
    }

    public function testLoginWrongPassword()
    {
        $data = ['username'=> 'administrator',
        'password'=> 'wrongPassword'];
        $adminDefaultUser = User::where('id', 1)->first();
        $adminDefaultUser['last_password_update'] = now();
        $adminDefaultUser->save();
        $response = $this->json('POST', '/api/login', $data)-> assertStatus(401);
    }

    public function testLoginPasswordPerished()
    {
        $data = ['username'=> 'administrator',
        'password'=> 'administrator'];
        $response = $this->json('POST', '/api/login', $data)-> assertStatus(428);
    }
}
