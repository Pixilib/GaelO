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

    public function testGetUserRoles()
    {

        AuthorizationTools::actAsAdmin(true);

        $this->createUserWithRoleInTwoStudies();

        $answer = $this->json('GET', '/api/users/4/roles');
        $answer->assertStatus(200);
        $content = json_decode($answer->content(), true);
        $numberofStudies = sizeof(array_keys($content));
        $numberOfRoleInstudy = sizeof($content[$this->studyName3Roles]);
        $this->assertEquals(2, $numberofStudies);
        $this->assertEquals(3, $numberOfRoleInstudy);
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

    public function testGetUserRolesShouldBeForbiddenByDifferentUserInNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $answer = $this->json('GET', '/api/users/1/roles');
        $answer->assertStatus(403);
    }

    public function testGetUserRolesShouldPassForDifferentUserIfAdmin()
    {
        AuthorizationTools::actAsAdmin(true);
        $answer = $this->json('GET', '/api/users/1/roles');
        $answer->assertStatus(200);
    }

    public function testGetUserRolesInStudy()
    {
        AuthorizationTools::actAsAdmin(true);
        $this->createUserWithRoleInTwoStudies();
        $content = $this->json('GET', '/api/users/4/roles/' . $this->studyName3Roles)->assertStatus(200)->content();
        $content = json_decode($content, true);
        //Expect to find 3 role for this user in this study
        $this->assertEquals(3, sizeof($content));
    }

    public function testCreateRoleForUser()
    {
        AuthorizationTools::actAsAdmin(true);
        //Create 2 random studies
        $study = Study::factory()->create();
        $payload = ["role" => "Investigator"];
        //First call should be success
        $this->json('POST', '/api/users/1/roles/' . $study->name, $payload)->assertStatus(201);
    }

    public function testCreateRoleNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        //Create 2 random studies
        $study = Study::factory()->create();
        $payload = ["role" => "Investigator"];
        //First call should be success
        $this->json('POST', '/api/users/1/roles/' . $study->name, $payload)->assertStatus(403);
    }

    public function testCreateAlreadyExistingRoleForUser()
    {
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $study->name);
        $payload = ["role" => "Investigator"];
        //Second call should answer no new role with status 400
        $this->json('POST', '/api/users/1/roles/' . $study->name, $payload)->assertStatus(400);
    }

    public function testDeleteUserRole()
    {
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $study->name);
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $study->name);
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_MONITOR, $study->name);

        //Delete Investigator role
        $this->json('DELETE', '/api/users/1/roles/' . $study->name . '/Investigator')->assertNoContent(200);
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
        $this->json('DELETE', '/api/users/1/roles/' . $study->name . '/Investigator')->assertNoContent(403);
    }

    public function testGetUserFromStudy()
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

        $answer = $this->json('GET', '/api/studies/' . $study->name . '/users/');
        $answer->assertStatus(200);
        $responseArray = json_decode($answer->content(), true);
        //Expect to have 5 users in the list
        $this->assertEquals(5, sizeof($responseArray));
    }

    public function testGetUserFromStudyForbiddenNotAdmin()
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

        $answer = $this->json('GET', '/api/studies/' . $study->name . '/users/');
        $answer->assertStatus(403);
    }

    public function testGetUserFromStudyAllowedForSupervisor()
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
        $answer = $this->json('GET', '/api/studies/' . $study->name . '/users/');
        $answer->assertStatus(200);
    }
}
