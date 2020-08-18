<?php

namespace Tests\Feature;
use Illuminate\Support\Facades\Http;
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
        $response = $this->json('POST', '/api/users', $data) -> assertSuccessful();
        //Test that copies don't insert
        $response = $this->json('POST', '/api/users', $data) -> assertStatus(500);

    }

    public function testGetUser()
    {
        $response = $this->json('GET', '/api/users/1') -> assertSuccessful();
        $response = $this->json('GET', '/api/users/-1') -> assertStatus(500);
    }

    //        $user = factory(User::class, 10)->create();

}
