<?php

namespace Tests\Feature\TestUser;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\TrackerRepository;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\AuthorizationTools;

class DeleteUserTest extends TestCase
{

    use RefreshDatabase;
    private MockInterface $trackerSpy;

    protected function setUp() : void{
        parent::setUp();
        $this->artisan('db:seed');
        User::factory()->count(5)->create();
        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
    }

    public function testDeleteUser() {
        $userId = AuthorizationTools::actAsAdmin(true);
        //Remove User number 3
        $this->json('DELETE', '/api/users/3')->assertSuccessful();
        //Check that the user 3 has been remove
        $queryUser = User::where('id', 3)->first();
        $this->assertEmpty($queryUser);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($userId, Constants::TRACKER_ROLE_USER, Mockery::any(), Mockery::any(), Constants::TRACKER_EDIT_USER, Mockery::any());
    }

    public function testDeleteUserShouldFailNotAdmin() {
        AuthorizationTools::actAsAdmin(false);
        //Remove number 3
        $this->json('DELETE', '/api/users/3')->assertStatus(403);
    }

    public function testDeleteNonExistingUser(){
        AuthorizationTools::actAsAdmin(true);
        //Test delete non-existing user should be refused
        $this->json('DELETE', '/api/users/8') -> assertStatus(404);
    }

    public function testReactivateUser(){
        $userId = AuthorizationTools::actAsAdmin(true);
        $payload = [];
        User::find(2)->delete();
        $this->json('POST', '/api/users/2/activate', $payload)->assertNoContent(200);
        $user = User::find(2)->toArray();
        $this->assertNull($user['email_verified_at']);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($userId, Constants::ROLE_ADMINISTRATOR, Mockery::any(), Mockery::any(), Constants::TRACKER_EDIT_USER, Mockery::any());

    }
}
