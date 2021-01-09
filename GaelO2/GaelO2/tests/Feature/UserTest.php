<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;

use Tests\TestCase;
use App\Model\User;
use App\Model\Study;
use App\Model\Role;
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

    protected function setUp() : void{
        parent::setUp();

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );
    }

    public function testGetUser() {
        $this->json('GET', '/api/users/1')
            ->assertStatus(200);
    }

    public function testGetUserShouldFailNotAdmin(){
        factory(User::class, 5)->create([
            'administrator'=> false,
            'status' => 'Activated'
        ]);

        Passport::actingAs(
            User::where('id',3)->first()
        );
        $this->json('GET', '/api/users/2')
        ->assertStatus(403);

    }

    public function testGetOwnUser(){
        factory(User::class, 5)->create([
            'status' => 'Activated'
        ]);

        Passport::actingAs(
            User::where('id',2)->first()
        );

        $this->json('GET', '/api/users/2')
        ->assertStatus(200);
    }

    public function testGetAllUsers(){
        //Fill user table
        factory(User::class, 5)->create();
        //Test get all users
        $this->json('GET', '/api/users')-> assertJsonCount(6);

    }

    public function testGetAllUserShouldFailNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        //Test get all users
        $this->json('GET', '/api/users')-> assertStatus(403);
    }

    public function testGetNotExistingUser(){
        //Test get non existing user
        $this->json('GET', '/api/users/3')-> assertStatus(404);
    }

    public function testGetUserRoles(){

        //Create 5 users
        $users = factory(User::class, 5)->create();
        //Create 2 random studies
        $studies = factory(Study::class, 2)->create();

        $users->each(function ($user) use ($studies)  {
            $studies->each(function ($study) use($user) {
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Investigator', 'study_name'=>$study->name]);
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Supervisor', 'study_name'=>$study->name]);
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Monitor', 'study_name'=>$study->name]);

            });

        });

        $answer = $this->json('GET', '/api/users/4/roles');
        $answer->assertStatus(200);
        $content = json_decode($answer->content(), true);
        $numberofStudies = sizeof(array_keys($content));
        $firstStudyName = array_keys($content)[0];
        $numberOfRoleInFirstStudy = sizeof($content[$firstStudyName]);
        $this->assertEquals(2 , $numberofStudies);
        $this->assertEquals(3, $numberOfRoleInFirstStudy);

    }

    public function testGetUserRolesShouldBeForbiddenByDifferentUserInNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $answer = $this->json('GET', '/api/users/1/roles');
        $answer->assertStatus(403);
    }

    public function testGetUserRolesShouldPassForDifferentUserIfAdmin(){
        AuthorizationTools::actAsAdmin(true);
        $answer = $this->json('GET', '/api/users/1/roles');
        $answer->assertStatus(200);
    }

    public function testGetUserRolesInStudy(){

        //Create 1 users
        $user = factory(User::class)->create();
        //Create 1 study
        $study = factory(Study::class)->create();

        factory(Role::class)->create(['user_id'=>$user->id, 'name'=>Constants::ROLE_INVESTIGATOR, 'study_name'=>$study->name]);
        factory(Role::class)->create(['user_id'=>$user->id, 'name'=>Constants::ROLE_SUPERVISOR, 'study_name'=>$study->name]);
        factory(Role::class)->create(['user_id'=>$user->id, 'name'=>Constants::ROLE_MONITOR, 'study_name'=>$study->name]);

        $content = $this->json('GET', '/api/users/'.$user->id.'/roles/'.$study->name)->content();
        $content = json_decode($content, true);
        //Expect to find 3 role for this user in this study
        $this->assertEquals(3, sizeof($content));

    }

    public function testCreateRoleForUser(){
        //Create 2 random studies
        $study = factory(Study::class)->create();
        $payload = ["role" => "Investigator"];
        //First call should be success
        $this->json('POST', '/api/users/1/roles/'.$study->name, $payload)->assertStatus(201);

    }

    public function testCreateRoleNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        //Create 2 random studies
        $study = factory(Study::class)->create();
        $payload = ["role" => "Investigator"];
        //First call should be success
        $this->json('POST', '/api/users/1/roles/'.$study->name, $payload)->assertStatus(403);

    }

    public function testCreateAlreadyExistingRoleForUser(){
        $study = factory(Study::class)->create();
        factory(Role::class)->create(['user_id'=>1, 'name'=>Constants::ROLE_INVESTIGATOR, 'study_name'=>$study->name]);
        $payload = ["role" => "Investigator"];
        //Second call should answer no new role with status 400
        $this->json('POST', '/api/users/1/roles/'.$study->name, $payload)->assertStatus(400);
    }

    public function testDeleteUserRole(){
        $study = factory(Study::class)->create();
        factory(Role::class)->create(['user_id'=>1, 'name'=>'Investigator', 'study_name'=>$study->name]);
        factory(Role::class)->create(['user_id'=>1, 'name'=>'Supervisor', 'study_name'=>$study->name]);
        factory(Role::class)->create(['user_id'=>1, 'name'=>'Monitor', 'study_name'=>$study->name]);

        //Delete Investigator role
        $this->json('DELETE', '/api/users/1/roles/'.$study->name.'/Investigator')->assertNoContent(200);
        //Check the user still have only 2 remaining roles
        $remainingroles = User::where('id',1)->first()->roles()->get();
        $this->assertEquals(2, sizeof($remainingroles->toArray()));
    }

    public function testDeleteRoleNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $study = factory(Study::class)->create();
        factory(Role::class)->create(['user_id'=>1, 'name'=>'Investigator', 'study_name'=>$study->name]);
        factory(Role::class)->create(['user_id'=>1, 'name'=>'Supervisor', 'study_name'=>$study->name]);
        factory(Role::class)->create(['user_id'=>1, 'name'=>'Monitor', 'study_name'=>$study->name]);

        //Delete Investigator role
        $this->json('DELETE', '/api/users/1/roles/'.$study->name.'/Investigator')->assertNoContent(403);
    }

    public function testGetUserFromStudy() {
        //Create a study
        $study = factory(Study::class)->create();

        //Create 5 users
        $users = factory(User::class, 5)->create();

        $users->each(function ($user) use ($study)  {
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Investigator', 'study_name'=>$study->name]);
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Supervisor', 'study_name'=>$study->name]);
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Monitor', 'study_name'=>$study->name]);
        });
        $answer = $this->json('GET', '/api/studies/'.$study->name.'/users/');
        $answer->assertStatus(200);
        $responseArray = json_decode($answer->content(), true);
        //Expect to have 5 users in the list
        $this->assertEquals(5, sizeof($responseArray));
    }

    public function testGetUserFromStudyForbiddenNotAdmin() {
        AuthorizationTools::actAsAdmin(false);
        //Create a study
        $study = factory(Study::class)->create();

        //Create 5 users
        $users = factory(User::class, 5)->create();

        $users->each(function ($user) use ($study)  {
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Investigator', 'study_name'=>$study->name]);
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Supervisor', 'study_name'=>$study->name]);
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Monitor', 'study_name'=>$study->name]);
        });
        $answer = $this->json('GET', '/api/studies/'.$study->name.'/users/');
        $answer->assertStatus(403);
    }

    public function testGetUserFromStudyAllowedForSupervisor() {
        //Create a study
        $study = factory(Study::class)->create();
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $study->name);

        //Create 5 users
        $users = factory(User::class, 5)->create();

        $users->each(function ($user) use ($study)  {
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Investigator', 'study_name'=>$study->name]);
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Supervisor', 'study_name'=>$study->name]);
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Monitor', 'study_name'=>$study->name]);
        });
        $answer = $this->json('GET', '/api/studies/'.$study->name.'/users/');
        $answer->assertStatus(200);
    }

}
