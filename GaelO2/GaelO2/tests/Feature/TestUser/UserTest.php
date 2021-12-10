<?php

namespace Tests\Feature\TestUser;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;

use Tests\TestCase;
use App\Models\User;
use App\Models\Study;
use Tests\AuthorizationTools;

class UserTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
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

    public function testGetAllUserShouldFailNotAdmin()
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

    //ICI DOIT ETRE DANS LA CLASS ?
    private function createUserWithRoleInTwoStudies()
    {
        //Create 5 users
        $users = User::factory()->count(5)->create();
        //Create 2 random studies
        $study = Study::factory()->count(2)->create();
        $studyName1 = $study->first()->name;
        $studyName2 = $study->last()->name;

        $this->studyName3Roles = $studyName1;
        $this->studyName1Roles = $studyName1;

        $users->each(function ($user) use ($studyName1, $studyName2) {
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_INVESTIGATOR, $studyName1);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_SUPERVISOR, $studyName1);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_REVIEWER, $studyName1);
            AuthorizationTools::addRoleToUser($user->id, Constants::ROLE_INVESTIGATOR, $studyName2);
        });
    }

    public function testGetUserRolesInStudy()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $study2 = Study::factory()->create();

        //Add Role for testing user
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $study->name);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $study->name);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $study->name);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $study2->name);

        $content = $this->json('GET', '/api/users/'.$currentUserId.'/studies/'.$study->name.'/roles')->assertStatus(200)->content();
        $content = json_decode($content);

        //Expect to find 3 role for this user in this study
        $this->assertEquals(3, sizeof($content));
    }

    public function testGetUserRolesInStudyShouldFailNoSameUserButAdmin()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(true);
        $this->createUserWithRoleInTwoStudies();
        $content = $this->json('GET', '/api/users/'.($currentUserId+1).'/studies/'.$this->studyName3Roles.'/roles');
        $content->assertStatus(200);
    }

    public function testGetUserRolesInStudyShouldFailNoSameUserNoAdmin()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $this->createUserWithRoleInTwoStudies();
        $content = $this->json('GET', '/api/users/'.($currentUserId+1).'/studies/'.$this->studyName3Roles.'/roles');
        $content->assertStatus(403);
    }

    public function testCreateRoleForUser()
    {
        AuthorizationTools::actAsAdmin(true);
        //Create 2 random studies
        $study = Study::factory()->create();
        $payload = ["role" => "Investigator"];
        //First call should be success
        $this->json('POST', '/api/users/1/studies/'.$study->name.'/roles/', $payload)->assertStatus(201);
    }

    public function testCreateRoleNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        //Create 2 random studies
        $study = Study::factory()->create();
        $payload = ["role" => "Investigator"];
        //First call should be success
        $this->json('POST', '/api/users/1/studies/'.$study->name.'/roles/', $payload)->assertStatus(403);
    }

    public function testCreateAlreadyExistingRoleForUser()
    {
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $study->name);
        $payload = ["role" => "Investigator"];
        //Second call should answer no new role with status 400
        $this->json('POST', '/api/users/1/studies/'.$study->name.'/roles/', $payload)->assertStatus(409);
    }

    public function testCreateNonAllowedRoleForAncillaryStudy()
    {
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $ancillaryStudy = Study::factory()->ancillaryOf($study->name)->create();
        $payload = ["role" => "Investigator"];
        //should be forbiden
        $this->json('POST', '/api/users/1/studies/'.$ancillaryStudy->name.'/roles/', $payload)->assertStatus(403);
    }

    public function testDeleteUserRole()
    {
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $study->name);
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $study->name);
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_MONITOR, $study->name);

        //Delete Investigator role
        $this->json('DELETE', '/api/users/1/studies/'.$study->name.'/roles/Investigator')->assertNoContent(200);
        //Check the user still have only 2 remaining roles
        $remainingroles = User::where('id', 1)->first()->roles()->get();
        $this->assertEquals(2, sizeof($remainingroles->toArray()));
    }

    public function testDeleteRoleNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $study = Study::factory()->create();
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $study->name);

        //Delete Investigator role
        $this->json('DELETE', '/api/users/1/studies/'.$study->name.'/roles/Investigator')->assertStatus(403);
    }

    public function testGetUserFromStudyAdministrator()
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

    public function testGetUserFromStudySupervisor()
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
        $this->assertEquals(4, sizeof( array_keys($responseArray[0]) ));
    }

    public function testGetUserFromStudyForbiddenNotAdminOrSupervisor()
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
