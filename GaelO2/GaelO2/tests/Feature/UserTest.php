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
        $this->json('GET', '/api/users') -> assertJsonCount(6);
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

        $response = $this->json('GET', '/api/users/4/roles');
        dd($response->content());

    }

}
