<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;

class CreateUserTest extends TestCase
{
    //Run Migration at each test
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }
    //Run migration and seeds before each test
    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    //This method is called before each test, it needs to call the parent setup methods
    protected function setUp() : void{
        parent::setUp();
        //Create an existing user using factory on user table (and store the resulting entity in this class)
        $this->alreadyExistingUser = factory(User::class)->create();
        //Define the valid payload the user creation should success
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
        //Fake an authentified user to pass auth security for the tests
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
        //Test that copies of existing user don't insert
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(400);
        //Use (and test) the GET api to get data of created user and check value are mathing the original request
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

    /**
     * Test that creating an existing user should fail
     */
    public function testCreateAlreadyExistingUser(){
        $this->validPayload['username'] = $this->alreadyExistingUser['username'];
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(400);
    }

    /**
     * Test that creating user with an already used email should fail
     */
    public function testCreateAlreadyExistingEmail(){
        $this->validPayload['email'] = $this->alreadyExistingUser['email'];
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(400);
    }

    /**
     * Test that creating user with missing data should fail
     */
    public function testCreateIncompleteData(){
        $mandatoryTags = ['username', 'email', 'job', 'centerCode', 'administrator'];
        foreach($mandatoryTags as $tag) {
            unset($this->validPayload[$tag]);
            $this->json('POST', '/api/users/', $this->validPayload)-> assertStatus(400);
        }
    }

    /**
     * Test that creating user with invalid email should fail
     */
    public function testCreateInvalidEmail(){
        $this->validPayload['email']= 'wrong';
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(400);
    }

    /**
     * Test that creating user with a phone number not only composed by digit should fail
     */
    public function testCreateInvalidPhone(){
        $this->validPayload['phone'] = "05G05";
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(400);
    }

    public function testCreateNoPhone(){
        $this->validPayload['phone'] = null;
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(201);
    }

    public function testCreateNoOrthancAddress(){
        $this->validPayload['orthancAddress'] = null;
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(201);
    }

    public function testCreateNoOrthancLogin(){
        $this->validPayload['orthancLogin'] = null;
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(201);
    }

    public function testCreateNoOrthancPassword(){
        $this->validPayload['orthancPassword'] = null;
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(201);
    }
}
