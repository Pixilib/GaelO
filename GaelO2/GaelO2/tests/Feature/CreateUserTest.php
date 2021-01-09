<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Model\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\AuthorizationTools;

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

    }

    /**
     * Test user creation with a valid payload
     */
    public function testCreateCorrectPayload()
    {
        AuthorizationTools::actAsAdmin(true);
        //Test user creation
        $this->json('POST', '/api/users', $this->validPayload)-> assertSuccessful();

        //Use (and test) the GET api to get data of created user and check value are mathing the original request
        $createdUser = User::where(['id'=>3])->get()->first()->toArray();

        //Check defaut value at user creation
        $this->assertEquals($createdUser['status'], Constants::USER_STATUS_UNCONFIRMED);
        $this->assertEquals($createdUser['phone'],  $this->validPayload['phone']);
        $this->assertNull($createdUser['last_password_update']);
    }

    public function testCreateCorrectPayloadShouldFailNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        //Test user creation
        $this->json('POST', '/api/users', $this->validPayload)-> assertStatus(403);
    }

    /**
     * Test that creating an existing user should fail
     */
    public function testCreateAlreadyExistingUser(){
        AuthorizationTools::actAsAdmin(true);
        $alreadyExistingUser = factory(User::class)->create();
        $this->validPayload['username'] = $alreadyExistingUser['username'];
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(409);
    }

    /**
     * Test that creating user with an already used email should fail
     */
    public function testCreateAlreadyExistingEmail(){
        AuthorizationTools::actAsAdmin(true);
        //Create an existing user using factory on user table (and store the resulting entity in this class)
        $alreadyExistingUser = factory(User::class)->create();
        $this->validPayload['email'] = $alreadyExistingUser['email'];
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(409);
    }

    /**
     * Test that creating user with missing data should fail
     */
    public function testCreateIncompleteData(){
        AuthorizationTools::actAsAdmin(true);
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
        AuthorizationTools::actAsAdmin(true);
        $this->validPayload['email']= 'wrong';
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(400);
    }

    /**
     * Test that creating user with a phone number not only composed by digit should fail
     */
    public function testCreateInvalidPhone(){
        AuthorizationTools::actAsAdmin(true);
        $this->validPayload['phone'] = "05G05";
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(400);
    }

    public function testCreateNoPhone(){
        AuthorizationTools::actAsAdmin(true);
        $this->validPayload['phone'] = null;
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(201);
    }

    public function testCreateNoOrthancAddress(){
        AuthorizationTools::actAsAdmin(true);
        $this->validPayload['orthancAddress'] = null;
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(201);
    }

    public function testCreateNoOrthancLogin(){
        AuthorizationTools::actAsAdmin(true);
        $this->validPayload['orthancLogin'] = null;
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(201);
    }

    public function testCreateNoOrthancPassword(){
        AuthorizationTools::actAsAdmin(true);
        $this->validPayload['orthancPassword'] = null;
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(201);
    }
}
