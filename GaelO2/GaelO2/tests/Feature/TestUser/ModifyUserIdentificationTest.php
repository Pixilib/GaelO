<?php

namespace Tests\Feature\TestUser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;

class ModifyUserIdentificationTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }


    public function testValidModifyUserIdentification()
    {

        //Save database state before update
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $beforeChangeUser = User::find($currentUserId);

        $validPayload = [
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'email' => 'test@test.fr',
            'phone' => '0101010101',
        ];

        //Update with update API, shoud be success
        $this->json('PATCH', '/api/users/'.$currentUserId, $validPayload)-> assertSuccessful();
        //Save after update
        $afterChangeUser = User::find($currentUserId)->toArray();

         //Value expected to have changed
         $updatedArray = ['email', 'lastname', 'firstname', 'email', 'phone'];
        //Check that key needed to be updated has been updated in database
        foreach($updatedArray as $key){
            $this->assertNotEquals($beforeChangeUser[$key], $afterChangeUser[$key]);
        }
    }

    public function testModifyIdentificationShouldFailNotSameUser(){

        AuthorizationTools::actAsAdmin(false);

        $validPayload = [
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'email' => 'test@test.fr',
            'phone' => '0101010101',
        ];

        //Update with update API, shoud be success
        $this->json('PATCH', '/api/users/1', $validPayload)-> assertStatus(403);

    }

    public function testModifyUserIdentificationAlreadyUsedEmail()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        $validPayload = [
            'lastname' => 'administrator',
            'firstname' => 'administrator',
            'email' => 'administrator@gaelo.fr',
            'phone' => '0101010101',
        ];

        $this->json('PATCH', '/api/users/'.$currentUserId, $validPayload)->assertStatus(409);
    }


}
