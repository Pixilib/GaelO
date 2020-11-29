<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ModifyUserIdentificationTest extends TestCase
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


        $this->user = factory(User::class)->create([
        'administrator'=>false,
        'center_code'=> 0 ]);

    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }


    public function testValidModifyUserIdentification()
    {

        //Save database state before update
        $beforeChangeUser = User::where('id',1)->first();

        $validPayload = [
            'username' => 'username',
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'email' => 'test@test.fr',
            'phone' => '0101010101',
        ];

        //Update with update API, shoud be success
        $this->json('PATCH', '/api/users/1', $validPayload)-> assertSuccessful();
        //Save after update
        $afterChangeUser = User::where('id',$this->user['id'])->get()->first()->toArray();

         //Value expected to have changed
         $updatedArray = ['username', 'lastname', 'firstname', 'email', 'phone'];
        //Check that key needed to be updated has been updated in database
        foreach($updatedArray as $key){
            $this->assertNotEquals($beforeChangeUser[$key], $afterChangeUser[$key]);
        }
    }

    public function testModifyIdentificationShouldFailNotSameUser(){


        $validPayload = [
            'username' => 'username',
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'email' => 'test@test.fr',
            'phone' => '0101010101',
        ];

        //Update with update API, shoud be success
        $this->json('PATCH', '/api/users/2', $validPayload)-> assertStatus(403);

    }

    public function testModifyUserIdentificationAlreadyUsedUsername()
    {
        factory(User::class)->create(['username' => 'Pris']);

        $validPayload = [
            'username' => 'Pris',
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'email' => 'test@test.fr',
            'phone' => '0101010101',
        ];

        //Update with update API, shoud be success
        $this->json('PATCH', '/api/users/1', $validPayload)->assertNoContent(409);
    }

    public function testModifyUserIdentificationAlreadyUsedEmail()
    {
        factory(User::class)->create(['email' => 'pris@pris.fr']);

        $validPayload = [
            'username' => 'administrator',
            'lastname' => 'administrator',
            'firstname' => 'administrator',
            'email' => 'pris@pris.fr',
            'phone' => '0101010101',
        ];

        $this->json('PATCH', '/api/users/1', $validPayload)->assertStatus(409);
    }


}
