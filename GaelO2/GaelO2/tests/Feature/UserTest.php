<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;

use Tests\TestCase;
use App\User;
use App\Study;
use App\Role;

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
        //Fill user table
        factory(User::class, 5)->create(["administrator"=>true]);
        //Test get user 4
        $this->json('GET', '/api/users/4')
            ->assertStatus(200)
            ->assertJsonFragment(['administrator'=>true]);
        //Test get all users
        $this->json('GET', '/api/users')-> assertJsonCount(6);
        //Test get incorrect user
        $this->json('GET', '/api/users/-1') -> assertStatus(500);
    }

    public function testGetUserRoles(){

        //Create 5 users
        $users = factory(User::class, 5)->create(["administrator"=>true]);
        //Create 2 random studies
        $studies = factory(Study::class, 2)->create();

        $users->each(function ($user) use ($studies)  {
            $studies->each(function ($study) use($user) {
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Investigator', 'study_name'=>$study->name]);
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Supervisor', 'study_name'=>$study->name]);
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Monitor', 'study_name'=>$study->name]);

            });

        });

        $content = $this->json('GET', '/api/users/4/roles')->content();
        $content = json_decode($content, true);
        $numberofStudies = sizeof(array_keys($content));
        $firstStudyName = array_keys($content)[0];
        $numberOfRoleInFirstStudy = sizeof($content[$firstStudyName]);
        $this->assertEquals(2 , $numberofStudies);
        $this->assertEquals(3, $numberOfRoleInFirstStudy);

    }

    public function testGetUserRolesInStudy(){

        //Create 5 users
        $users = factory(User::class, 5)->create(["administrator"=>true]);
        //Create 2 random studies
        $studies = factory(Study::class, 2)->create();

        $users->each(function ($user) use ($studies)  {
            $studies->each(function ($study) use($user) {
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Investigator', 'study_name'=>$study->name]);
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Supervisor', 'study_name'=>$study->name]);
                factory(Role::class)->create(['user_id'=>$user->id, 'name'=>'Monitor', 'study_name'=>$study->name]);

            });

        });
        $studyName = $studies->first()['name'];
        $content = $this->json('GET', '/api/users/4/roles/'.$studyName)->content();
        $content = json_decode($content, true);
        $numberOfRoleInFirstStudy = sizeof($content);
        $this->assertEquals(3, $numberOfRoleInFirstStudy);

    }

    public function testCreateRoleForUser(){
        //Create 2 random studies
        $studies = factory(Study::class, 1)->create();

        $studyName = $studies->first()['name'];
        $payload = ["Investigator", "Supervisor"];
        //First call should be success
        $this->json('POST', '/api/users/1/roles/'.$studyName, $payload)->assertNoContent(201);
        //Second call should answer no new role with status 400
        $this->json('POST', '/api/users/1/roles/'.$studyName, $payload)->assertNoContent(400);

    }

    public function testDeleteUserRole(){
        //Create 2 random studies
        $studies = factory(Study::class, 1)->create();
        $studies->each(function ($study)  {
            factory(Role::class)->create(['user_id'=>1, 'name'=>'Investigator', 'study_name'=>$study->name]);
            factory(Role::class)->create(['user_id'=>1, 'name'=>'Supervisor', 'study_name'=>$study->name]);
            factory(Role::class)->create(['user_id'=>1, 'name'=>'Monitor', 'study_name'=>$study->name]);

        });
        $studyName = $studies->first()['name'];
        //Delete Investigator role
        $this->json('DELETE', '/api/users/1/roles/'.$studyName.'/Investigator')->assertNoContent(200);
        //Check the user still have only 2 remaining roles
        $remainingroles = User::where('id',1)->first()->roles()->get();
        $this->assertEquals(2, sizeof($remainingroles->toArray()));

    }

}
