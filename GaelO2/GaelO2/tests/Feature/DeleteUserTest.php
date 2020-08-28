<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\User;

class DeleteUser extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    public function testDeleteUser() {
        //Fill user table
        factory(User::class, 5)->create();
        //Remove number 3
        $this->json('DELETE', '/api/users/3') -> assertSuccessful();
        //Test delete non-existing user should be refused
        $this->json('DELETE', '/api/users/-1') -> assertStatus(500);
        //Check that the user 3 has been remove
        $queryUser = User::where('id', 3)->first();
        $this->assertEmpty($queryUser);
    }
}
