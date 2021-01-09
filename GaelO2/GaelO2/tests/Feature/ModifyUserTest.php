<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\User;
use App\Models\Center;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\AuthorizationTools;

class ModifyUserTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected function setUp() : void{
        parent::setUp();
        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );

        $center = factory(Center::class)->create();

        $this->user = factory(User::class)->create(['status'=>'Activated',
        'administrator'=>false,
        'job' => 'Supervision',
        'center_code'=> 0 ]);

        $this->validPayload = [
            'username' => 'username',
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'email' => 'test@test.fr',
            'phone' => '0101010101',
            'status' => 'Blocked',
            'administrator' => true,
            'centerCode' => $center->code,
            'job' => 'CRA',
            'orthancAddress'=> 'http://gaelo.fr',
            'orthancLogin'=>'gaelo',
            'orthancPassword'=>'gaelo',
        ];

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
        //Save database state before update
        $beforeChangeUser = User::where('id',$this->user['id'])->get()->first()->toArray();
        //Update with update API, shoud be success
        $resp = $this->json('PUT', '/api/users/'.$this->user['id'], $this->validPayload)-> assertSuccessful();
        //Save after update
        $afterChangeUser = User::where('id',$this->user['id'])->get()->first()->toArray();

         //Value expected to have changed
         $updatedArray = ['username', 'lastname', 'firstname', 'email', 'phone', 'status',
         'administrator', 'center_code', 'job', 'orthanc_address', 'orthanc_login', 'orthanc_password'];
        //Check that key needed to be updated has been updated in database
        foreach($updatedArray as $key){
            $this->assertNotEquals($beforeChangeUser[$key], $afterChangeUser[$key]);
        }
        //Check value not supposed to change by edition
        $notUpdatedArray = ['password', 'password_previous1', 'password_previous2', 'last_password_update',
        'creation_date'];
        foreach($notUpdatedArray as $key){
            $this->assertEquals($beforeChangeUser[$key], $afterChangeUser[$key]);
        }

    }

    public function testModifyForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $this->json('PUT', '/api/users/'.$this->user['id'], $this->validPayload)-> assertStatus(403);
    }

    public function testWrongEmailValue(){
        AuthorizationTools::actAsAdmin(true);
        $this->validPayload['email']='wrong email';
        $this->json('PUT', '/api/users/'.$this->user['id'], $this->validPayload)
        -> assertStatus(400);
    }

    public function testUncompleteRequest(){
        AuthorizationTools::actAsAdmin(true);
        $mandatoryTags = ['username', 'email', 'job', 'centerCode', 'administrator'];
        foreach($mandatoryTags as $tag) {
            unset($this->validPayload[$tag]);
            $this->json('PUT', '/api/users/'.$this->user['id'], $this->validPayload)-> assertStatus(400);
        }
    }

    public function testUsingAlreadyUsedUsername(){
        AuthorizationTools::actAsAdmin(true);
        $this->validPayload['username']='administrator';
        $this->json('PUT', '/api/users/'.$this->user['id'], $this->validPayload)
        -> assertStatus(409);
    }

    public function testUsingAlreadyUsedEmail(){
        AuthorizationTools::actAsAdmin(true);
        $this->validPayload['email'] = "administrator@gaelo.fr";
        $this->json('PUT', '/api/users/'.$this->user['id'], $this->validPayload)
        -> assertStatus(409);
    }

    public function testMakeAccountUnconfirmed(){
        AuthorizationTools::actAsAdmin(true);
        $this->validPayload['status'] = Constants::USER_STATUS_UNCONFIRMED;
        $this->json('PUT', '/api/users/'.$this->user['id'], $this->validPayload)
        -> assertStatus(200);
        $updatedUser = User::where('id', $this->user['id'])->first();
        $this->assertEquals(Constants::USER_STATUS_UNCONFIRMED, $updatedUser['status']);
        $this->assertNotSame($updatedUser['password_temporary'], $this->user['password_temporary']);

    }

}
