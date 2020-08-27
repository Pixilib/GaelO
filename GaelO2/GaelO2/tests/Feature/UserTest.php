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
        $response = $this->json('POST', '/api/users', $data)-> assertSuccessful();
        //Test that copies don't insert
        $response = $this->json('POST', '/api/users', $data) -> assertStatus(500);

    }

    public function testGetUser() {
        //Test get user 1
        $response = $this->json('GET', '/api/users/1')-> assertSuccessful();
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

    public function testModifyUser(){

        $user = factory(User::class)->create();
        $user2 = factory(User::class)->create(['username' => 'salim', 'email'=>'salim.kanoun@gmail.com']);

        $validRequest = [
            'username' => $user['username'],
            'lastname' => $user['lastname'],
            'firstname' => $user['firstname'],
            'email' => $user['email'],
            'phone' =>$user['phone'],
            'status' =>$user['status'],
            'administrator' =>$user['administrator'],
            'center_code' => $user['center_code'],
            'job' => $user['job'],
            'orthanc_address'=>$user['orthanc_address'],
            'orthanc_login'=>$user['orthanc_login'],
            'orthanc_password'=>$user['orthanc_password'],
        ];

        $response = $this->json('PUT', '/api/users/'.$user['id'], $validRequest)-> assertSuccessful();

        $wrongEmailRequest = $validRequest;
        $wrongEmailRequest['email'] = 'wrong';
        $response = $this->json('PUT', '/api/users/'.$user['id'], $wrongEmailRequest);
        $response-> assertStatus(400);

        $incompleteRequest = $validRequest;
        unset($incompleteRequest['phone']);
        $response = $this->json('PUT', '/api/users/'.$user['id'], $incompleteRequest);
        $response-> assertStatus(400);

        $alreadyUsedUser = $validRequest;
        $alreadyUsedUser['username'] = 'salim';
        $response = $this->json('PUT', '/api/users/'.$user['id'], $alreadyUsedUser);
        $response-> assertStatus(400);

        $alreadyUsedEmail = $validRequest;
        $alreadyUsedEmail['email'] = "salim.kanoun@gmail.com";
        $response = $this->json('PUT', '/api/users/'.$user['id'], $alreadyUsedEmail);
        $response-> assertStatus(400);


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
        $response = $this->json('PUT', '/api/users/'+$data['id']+'/password', $data);
        dd($response);
        //Test password format incorrect
        $data['password1'] = 'test';
        $data['password2'] = $data['password1'];
        $response = $this->json('PUT', '/api/users/'+$data['id'], $data) -> assertStatus(400);
        $response -> dump();
        //Test two passwords do not match
        $data['password2'] = 'CeciEst1nveautest';
        $response = $this->json('PUT', '/api/users'+$data['id'], $data) -> assertStatus(400);
        //Test previously used password
        $data['password1'] = 'Cecietait1test';
        $data['password2'] = $data['password1'];
        $response = $this->json('PUT', '/api/users'+$data['id'], $data) -> assertStatus(400);
    }

}
