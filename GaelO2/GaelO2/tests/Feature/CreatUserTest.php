<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\User;

class CreatUserTest extends TestCase
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
        $this->alreadyExistingUser = factory(User::class)->create();
        $this->validPayload =
        ['username' => 'truc',
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
    }

    /**
     * Test user creation with a valid payload
     */
    public function testCreateCorrectPayload()
    {
        //Test user creation
        $this->json('POST', '/api/users', $this->validPayload)-> assertSuccessful();
        //Test that copies don't insert
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(400);

        $createdUser = User::where('username', $this->validPayload['username'])->first();

        //Check that the created entity have the correcte values
        foreach($this->validPayload as $key=>$value){
            $this->assertEquals($this->validPayload[$key], $createdUser[$key]);
        }

        //Check defaut value at user creation
        $this->assertEquals($createdUser['status'], Constants::USER_STATUS_UNCONFIRMED);
        $this->assertNotNull($createdUser['password_temporary']);
        $this->assertNull($createdUser['password']);
        $this->assertNull($createdUser['previous_password1']);
        $this->assertNull($createdUser['previous_password2']);
        $this->assertNull($createdUser['last_password_update']);
    }

    public function testCreateAlreadyExistingUser(){
        $this->validPayload['username'] = $this->alreadyExistingUser['username'];
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(400);
    }

    public function testCreateAlreadyExistingEmail(){
        $this->validPayload['email'] = $this->alreadyExistingUser['email'];
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(400);
    }

    public function testCreateIncompleteData(){
        unset($this->validPayload['lastname']);
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(400);
    }

    public function testCreateInvalidEmail(){
        $this->validPayload['email']= 'wrong';
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(400);
    }

    public function testCreateInvalidPhone(){
        $this->validPayload['phone'] = "05G05";
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(400);
    }
}
