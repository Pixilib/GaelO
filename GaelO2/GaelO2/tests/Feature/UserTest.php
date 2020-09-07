<?php

namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;

use Tests\TestCase;
use App\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;

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

}
