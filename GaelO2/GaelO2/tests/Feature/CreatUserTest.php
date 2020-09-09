<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;

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
        'centerCode' => 0,
        'job' => 'Monitor',
        'orthancAddress' => 'test',
        'orthancLogin' => 'test',
        'orthancPassword' => 'test'];

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );
    }

    /**
     * Test user creation with a valid payload
     */
    public function testCreateCorrectPayload()
    {
        //Test user creation
        $resp = $this->json('POST', '/api/users', $this->validPayload)-> assertSuccessful();
        //Test that copies don't insert
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(400);

        $createdUser = $this->json('GET', '/api/users/3')->content();
        $createdUser = json_decode($createdUser, true);

        //Check that the created entity have the correcte values
        foreach($this->validPayload as $key=>$value){
            $this->assertEquals($this->validPayload[$key], $createdUser[$key]);
        }

        //Check defaut value at user creation
        $this->assertEquals($createdUser['status'], Constants::USER_STATUS_UNCONFIRMED);
        $this->assertNull($createdUser['lastPasswordUpdate']);
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
