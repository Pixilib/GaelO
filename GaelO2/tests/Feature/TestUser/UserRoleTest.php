<?php

namespace Tests\Feature\TestUser;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\TrackerRepository;
use App\Models\Role;
use App\Models\Study;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\AuthorizationTools;
use Tests\TestCase;

class UserRoleTest extends TestCase
{

    use RefreshDatabase;
    private string $studyName1Roles;
    private string $studyName3Roles;
    private MockInterface $trackerSpy;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
    }

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

        $content = $this->json('GET', '/api/users/'.$currentUserId.'/roles?studyName='.$study->name)->assertStatus(200)->content();
        $content = json_decode($content);

        //Expect to find 3 role for this user in this study
        $this->assertEquals(3, sizeof($content));
    }

    public function testGetUserRolesInStudyShouldFailNoSameUserButAdmin()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(true);
        $this->createUserWithRoleInTwoStudies();
        $content = $this->json('GET', '/api/users/'.($currentUserId+1).'/roles?studyName='.$this->studyName3Roles);
        $content->assertStatus(200);
    }

    public function testGetUserRolesInStudyShouldFailNoSameUserNoAdmin()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $this->createUserWithRoleInTwoStudies();
        $content = $this->json('GET', '/api/users/'.($currentUserId+1).'/roles?studyName='.$this->studyName3Roles);
        $content->assertStatus(403);
    }

    public function testCreateRoleForUser()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(true);
        //Create 2 random studies
        $study = Study::factory()->create();
        $payload = ["role" => "Investigator"];
        //First call should be success
        $this->json('POST', '/api/users/1/roles?studyName='.$study->name, $payload)->assertStatus(201);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_ADMINISTRATOR, Mockery::any(), Mockery::any(), Constants::TRACKER_EDIT_USER_ROLE, Mockery::any());
    }

    public function testCreateRoleNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        //Create 2 random studies
        $study = Study::factory()->create();
        $payload = ["role" => "Investigator"];
        //First call should be success
        $this->json('POST', '/api/users/1/roles?studyName='.$study->name, $payload)->assertStatus(403);
    }

    public function testCreateRoleBySupervisor()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        //Create 2 random studies
        $study = Study::factory()->create();
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $study->name);
        $payload = ["role" => "Investigator"];
        //First call should be success
        $this->json('POST', '/api/users/1/roles?studyName='.$study->name, $payload)->assertStatus(201);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($userId, Constants::ROLE_ADMINISTRATOR, Mockery::any(), Mockery::any(), Constants::TRACKER_EDIT_USER_ROLE, Mockery::any());
    }

    public function testCreateRoleBySupervisorShouldFailNoRole()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        //Create 2 random studies
        $study = Study::factory()->count(2)->create();
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $study->first()->name);
        $payload = ["role" => "Investigator"];
        //First call should be success
        $this->json('POST', '/api/users/1/roles?studyName='.$study->last()->name, $payload)->assertStatus(403);
    }

    public function testCreateAlreadyExistingRoleForUser()
    {
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $study->name);
        $payload = ["role" => "Investigator"];
        //Second call should answer no new role with status 400
        $this->json('POST', '/api/users/1/roles?studyName='.$study->name, $payload)->assertStatus(409);
    }

    public function testCreateNonAllowedRoleForAncillaryStudy()
    {
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $ancillaryStudy = Study::factory()->ancillaryOf($study->name)->create();
        $payload = ["role" => "Investigator"];
        //should be forbiden
        $this->json('POST', '/api/users/1/roles?studyName='.$ancillaryStudy->name, $payload)->assertStatus(403);
    }

    public function testDeleteUserRole()
    {
        $userId = AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $study->name);
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $study->name);
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_MONITOR, $study->name);

        //Delete Investigator role
        $this->json('DELETE', '/api/users/1/roles/Investigator?studyName='.$study->name)->assertNoContent(200);
        //Check the user still have only 2 remaining roles
        $remainingroles = User::where('id', 1)->first()->roles()->get();
        $this->assertEquals(2, sizeof($remainingroles->toArray()));
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($userId, Constants::ROLE_ADMINISTRATOR, Mockery::any(), Mockery::any(), Constants::TRACKER_EDIT_USER_ROLE, Mockery::any());
    }

    public function testDeleteRoleNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $study = Study::factory()->create();
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $study->name);

        //Delete Investigator role
        $this->json('DELETE', '/api/users/1/roles/Investigator?studyName='.$study->name)->assertStatus(403);
    }

    public function testDeleteRoleFromSupervisor()
    {
        $study = Study::factory()->create();
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $study->name);
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $study->name);

        //Delete Investigator role
        $this->json('DELETE', '/api/users/1/roles/Investigator?studyName='.$study->name)->assertStatus(200);
    }

    public function testDeleteRoleFromSupervisorShouldFailNoRole()
    {
        $study = Study::factory()->create();
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $study->name);
        $userId = AuthorizationTools::actAsAdmin(false);
        $study2 = Study::factory()->create();
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $study2->name);

        //Delete Investigator role
        $this->json('DELETE', '/api/users/1/roles/Investigator?studyName='.$study->name)->assertStatus(403);
    }


    public function testGetUserRole()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        $role = Role::factory()->userId($userId)->validatedDocumentationVersion('2.0.0')->create();
        $answer = $this->json('GET', 'api/users/' . $role->user_id . '/studies/' . $role->study_name . '/roles/' . $role->name->value);
        $response = json_decode($answer->content(), true);
        $this->assertArrayHasKey('validatedDocumentationVersion', $response);
        $this->assertArrayHasKey('study', $response);
        $this->assertEquals('2.0.0', $response['validatedDocumentationVersion']);
    }

    public function testModifyUserRoleValidatedDocumentation()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        $role = Role::factory()->userId($userId)->validatedDocumentationVersion('2.0.0')->create();
        $answer = $this->json('PUT', 'api/users/' . $role->user_id . '/studies/' . $role->study_name . '/roles/' . $role->name->value . '/validated-documentation', ['version' => '5.0.0']);
        $answer->assertSuccessful();
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($userId, $role->name->value, Mockery::any(), Mockery::any(), Constants::TRACKER_VALIDATED_DOCUMENTATION, Mockery::any());
    }
}
