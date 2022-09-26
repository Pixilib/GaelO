<?php

namespace Tests\Feature\TestUser;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class CreateUserTest extends TestCase
{
    //Run Migration at each test
    use RefreshDatabase;

    //This method is called before each test, it needs to call the parent setup methods
    protected function setUp() : void{
        parent::setUp();
        $this->artisan('db:seed');
        //Define the valid payload the user creation should success
        $this->validPayload =
        ['lastname' => 'truc',
        'firstname' => 'truc',
        'email' => 'truc@truc.fr',
        'phone' => '+33598653256',
        'administrator' => true,
        'centerCode' => 0,
        'job' => 'Monitor',
        'orthancAddress' => 'test',
        'orthancLogin' => 'test',
        'orthancPassword' => 'test'];

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
        $this->assertNull($createdUser['email_verified_at']);
        $this->assertEquals($createdUser['phone'],  $this->validPayload['phone']);
    }

    public function testCreateCorrectPayloadShouldFailNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        //Test user creation
        $this->json('POST', '/api/users', $this->validPayload)-> assertStatus(403);
    }

    /**
     * Test that creating user with an already used email should fail
     */
    public function testCreateAlreadyExistingEmail(){
        AuthorizationTools::actAsAdmin(true);
        //Create an existing user using factory on user table (and store the resulting entity in this class)
        $alreadyExistingUser =  User::factory()->create();
        $this->validPayload['email'] = $alreadyExistingUser['email'];
        $this->json('POST', '/api/users', $this->validPayload) -> assertStatus(409);
    }

    /**
     * Test that creating user with missing data should fail
     */
    public function testCreateIncompleteData(){
        AuthorizationTools::actAsAdmin(true);
        $mandatoryTags = ['email', 'job', 'centerCode', 'administrator'];
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
        $this->validPayload['phone'] = "05'é('('5487956";
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
