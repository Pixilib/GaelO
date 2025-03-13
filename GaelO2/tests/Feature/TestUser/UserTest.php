<?php

namespace Tests\Feature\TestUser;

use App\GaelO\Constants\Constants;

use Tests\TestCase;
use App\Models\User;
use App\Models\Study;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function testGetUser()
    {
        AuthorizationTools::actAsAdmin(true);
        $this->json('GET', '/api/users/1')
            ->assertStatus(200);
    }

    public function testGetUserShouldFailNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', '/api/users/1')
            ->assertStatus(403);
    }

    public function testGetOwnUser()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        $this->json('GET', '/api/users/' . $currentUserId)
            ->assertStatus(200);
    }

    public function testGetAllUsers()
    {
        AuthorizationTools::actAsAdmin(true);
        //Test get all users ( 2 in databat : current user + default user)
        $this->json('GET', '/api/users')->assertJsonCount(2);
    }

    public function testGetAllUsersWithDeleted()
    {
        AuthorizationTools::actAsAdmin(true);
        $users = User::factory()->count(5)->create();
        $users->first()->delete();
        //Test get all users ( 5 in databat : current user + default user + 5 created)
        $this->json('GET', '/api/users?withTrashed')->assertJsonCount(7);
    }

    public function testGetAllUsersShouldFailNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        //Test get all users
        $this->json('GET', '/api/users')->assertStatus(403);
    }

    public function testGetNotExistingUser()
    {
        AuthorizationTools::actAsAdmin(true);
        //Test get non existing user
        $this->json('GET', '/api/users/3')->assertStatus(404);
    }

    
    public function testGetUsersFromStudyAdministrator()
    {
        AuthorizationTools::actAsAdmin(true);
        //Create a study
        $study = Study::factory()->create();
        //Create 5 users
        $users = User::factory()->count(5)->create();

        $users->each(function ($user) use ($study) {
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_INVESTIGATOR, $study->name);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_SUPERVISOR, $study->name);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_MONITOR, $study->name);;
        });

        $answer = $this->json('GET', '/api/studies/' . $study->name . '/users?role=Administrator');
        $answer->assertStatus(200);
        $responseArray = json_decode($answer->content(), true);
        //Expect to have 5 users in the list
        $this->assertEquals(5, sizeof($responseArray));
        //Each User has full details
        $this->assertEquals(17, sizeof( array_keys($responseArray[0]) ));
    }

    public function testGetUsersFromStudySupervisor()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        $study = Study::factory()->create();

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $study->name);

        $users = User::factory()->count(5)->create();

        $users->each(function ($user) use ($study) {
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_INVESTIGATOR, $study->name);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_SUPERVISOR, $study->name);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_MONITOR, $study->name);
        });
        $answer = $this->json('GET', '/api/studies/' . $study->name . '/users?role=Supervisor');
        $answer->assertStatus(200);
        $responseArray = json_decode($answer->content(), true);
        //Expect to have 5 users in the list
        $this->assertEquals(6, sizeof($responseArray));
        //Each User has limited details
        $this->assertEquals(10, sizeof( array_keys($responseArray[0]) ));
    }

    public function testGetUsersFromStudyForbiddenNotAdminOrSupervisor()
    {
        AuthorizationTools::actAsAdmin(false);
        //Create 5 users
        $users = User::factory()->count(5)->create();
        $study = Study::factory()->create();

        $users->each(function ($user) use ($study) {
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_INVESTIGATOR, $study->name);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_SUPERVISOR, $study->name);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_MONITOR, $study->name);
        });

        $answer = $this->json('GET', '/api/studies/' . $study->name . '/users?role=Supervisor');
        $answer->assertStatus(403);
    }

    public function testGetStudiesFromUser(){

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $study = Study::factory()->count(2)->create();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $study->first()->name);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $study->last()->name);

        //Delete one study that should'nt appear in results
        $study->first()->delete();

        $answer = $this->json('GET', '/api/users/' . $currentUserId . '/studies/');
        $answer->assertStatus(200);
        $data = json_decode($answer->content());
        $this->assertEquals(1, sizeof( $data));
    }

    public function testGetStudiesFromUserShouldFailNotCurrentUser(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $study = Study::factory()->create();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $study->name);
        $answer = $this->json('GET', '/api/users/' . ($currentUserId+1) . '/studies/');
        $answer->assertStatus(403);

    }
}
