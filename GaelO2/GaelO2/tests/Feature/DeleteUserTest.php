<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;

class DeleteUserTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

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

    public function testDeleteUser() {

        //Fill user table
        factory(User::class, 5)->create();
        //Remove number 3
        $this->json('DELETE', '/api/users/3')->assertSuccessful();
        //Check that the user 3 has been remove
        $queryUser = User::where('id', 3)->first();
        $this->assertEmpty($queryUser);
    }

    public function testDeleteNonExistingUser(){
        //Test delete non-existing user should be refused
        $this->json('DELETE', '/api/users/8') -> assertStatus(500);
    }

    public function testReactivateUser(){

        $payload = [];
        User::find(1)->delete();
        $this->json('PATCH', '/api/users/1/reactivate', $payload)->assertNoContent(200);
        $user = User::find(1)->toArray();
        $this->assertEquals(Constants::USER_STATUS_UNCONFIRMED, $user['status']);

    }
}
