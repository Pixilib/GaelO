<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\User;
use App\Center;

class ModifyUserTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected function setUp() : void{
        parent::setUp();
        factory(Center::class)->create(['code'=>3]);
        $user = factory(User::class)->create(['status'=>'Activated',
        'administrator'=>false,
        'job' => 'Supervision',
        'center_code'=>3]);
        factory(User::class)->create(['username' => 'salim', 'email'=>'salim.kanoun@gmail.com']);
        $this->validPayload = [
            'username' => 'username',
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'email' => 'test@test.fr',
            'phone' => '0101010101',
            'status' => 'Blocked',
            'administrator' => true,
            'center_code' => 0,
            'job' => 'CRA',
            'orthanc_address'=> 'http://gaelo.fr',
            'orthanc_login'=>'gaelo',
            'orthanc_password'=>'gaelo',
        ];
        $this->user = $user;

    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testValidModify()
    {
        $this->json('PUT', '/api/users/'.$this->user['id'], $this->validPayload)
        -> assertSuccessful();

        $updatedUser = User::where('id', $this->user['id'])->first();

        $updatedArray = ['username', 'lastname', 'firstname', 'email', 'phone', 'status',
        'administrator', 'center_code', 'job', 'orthanc_address', 'orthanc_login', 'orthanc_password'];

        //Check that key needed to be updated has been updated in database
        foreach($updatedArray as $key){
            $this->assertNotEquals($this->user[$key], $updatedUser[$key]);
        }

        $notUpdatedArray = ['password', 'previous_password1', 'previous_password2', 'last_password_update',
        'creation_date'];
        foreach($notUpdatedArray as $key){
            $this->assertEquals($this->user[$key], $updatedUser[$key]);
        }

    }

    public function testWrongEmailValue(){
        $this->validPayload['email']='wrong email';
        $this->json('PUT', '/api/users/'.$this->user['id'], $this->validPayload)
        -> assertStatus(400);
    }

    public function testUncompleteRequest(){
        unset($this->validPayload['phone']);
        $this->json('PUT', '/api/users/'.$this->user['id'], $this->validPayload)
        -> assertStatus(400);
    }

    public function testUsingAlreadyUsedUsername(){
        $this->validPayload['username']='salim';
        $this->json('PUT', '/api/users/'.$this->user['id'], $this->validPayload)
        -> assertStatus(400);
    }

    public function testUsingAlreadyUsedEmail(){
        $this->validPayload['email'] = "salim.kanoun@gmail.com";
        $this->json('PUT', '/api/users/'.$this->user['id'], $this->validPayload)
        -> assertStatus(400);
    }

    public function testMakeAccountUnconfirmed(){
        $this->validPayload['status'] = Constants::USER_STATUS_UNCONFIRMED;
        $this->json('PUT', '/api/users/'.$this->user['id'], $this->validPayload)
        -> assertStatus(200);
        $updatedUser = User::where('id', $this->user['id'])->first();
        $this->assertEquals(Constants::USER_STATUS_UNCONFIRMED, $updatedUser['status']);
        $this->assertNotSame($updatedUser['password_temporary'], $this->user['password_temporary']);

    }
}
