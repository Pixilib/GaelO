<?php

namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\User;

class UserTest extends TestCase
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

    public function testCreateUser() {

        $data = ['username' => 'truc',
        'lastname' => 'truc',
        'firstname' => 'truc',
        'email' => 'truc@truc.fr',
        'phone' => '0600000000',
        'administrator' => true,
        'center_code' => 0,
        'job' => 'Monitor',
        'orthanc_address' => 'test',
        'orthanc_login' => 'test',
        'orthanc_password' => 'test'];

        //Test user creation
        $response = $this->json('POST', '/api/users', $data);
        dd($response);// -> assertSuccessful();
        //Test that copies don't insert
        $response = $this->json('POST', '/api/users', $data) -> assertStatus(500);

    }

    public function testGetUser() {
        //Test get user 1
        $response = $this->json('GET', '/api/users/1') -> assertSuccessful();
        //Test get all users
        $response = $this->json('GET', '/api/users') -> assertSuccessful();
        //Test get incorrect user
        $response = $this->json('GET', '/api/users/-1') -> assertStatus(500);
    }

    public function testDeleteUser() {
        //Test delete first user
        $response = $this->json('DELETE', '/api/users/1') -> assertSuccessful();
        //Test delete non-existing user
        $response = $this->json('DELETE', '/api/users/-1') -> assertStatus(500);
    }

    public function testChangePassword() {
        $user = factory(User::class)->create(['password' => 'Ceciest1test', 'status' => 'Activated', 'password_previous1' => 'Cecietait1test']);
        $data = [
            'id' => 2,
            'previous_password' => 'Ceciest1test',
            'password1' => 'Ceciest1nveautest',
            'password2' => 'Ceciest1nveautest'
        ];

        //Test data correctly updated
        $response = $this->json('PATCH', '/api/users', $data) -> assertStatus(200);
        //Test password format incorrect
        $data['password1'] = 'test';
        $data['password2'] = $data['password1'];
        $response = $this->json('PATCH', '/api/users', $data) -> assertStatus(400);
        $response -> dump();
        //Test two passwords do not match
        $data['password2'] = 'CeciEst1nveautest';
        $response = $this->json('PATCH', '/api/users', $data) -> assertStatus(400);
        //Test previously used password
        $data['password1'] = 'Cecietait1test';
        $data['password2'] = $data['password1'];
        $response = $this->json('PATCH', '/api/users', $data) -> assertStatus(400);
    }

}
