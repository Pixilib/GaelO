<?php

namespace Tests\Feature\TestUser;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\TrackerRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\AuthorizationTools;
use Tests\TestCase;

class ModifyUserIdentificationTest extends TestCase
{

    use RefreshDatabase;
    private User $user;
    private MockInterface $trackerSpy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
    }

    public function testValidModifyUserIdentification()
    {

        //Save database state before update
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $beforeChangeUser = User::find($currentUserId);

        $validPayload = [
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'email' => 'test@test.fr',
            'phone' => '0101010101',
            'enableEmailNotifications' => false
        ];

        //Update with update API, shoud be success
        $this->json('PATCH', '/api/users/'.$currentUserId, $validPayload)-> assertSuccessful();
        //Save after update
        $afterChangeUser = User::find($currentUserId)->toArray();

         //Value expected to have changed
         $updatedArray = ['email', 'lastname', 'firstname', 'email', 'phone', 'enable_email_notifications'];
        //Check that key needed to be updated has been updated in database
        foreach($updatedArray as $key){
            $this->assertNotEquals($beforeChangeUser[$key], $afterChangeUser[$key]);
        }
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::TRACKER_ROLE_USER, Mockery::any(), Mockery::any(), Constants::TRACKER_EDIT_USER, Mockery::any());
    }

    public function testModifyIdentificationShouldFailNotSameUser(){

        AuthorizationTools::actAsAdmin(false);

        $validPayload = [
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'email' => 'test@test.fr',
            'phone' => '0101010101',
        ];

        //Update with update API, shoud be success
        $this->json('PATCH', '/api/users/1', $validPayload)-> assertStatus(403);

    }

    public function testModifyUserIdentificationAlreadyUsedEmail()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        $validPayload = [
            'lastname' => 'administrator',
            'firstname' => 'administrator',
            'email' => 'administrator@gaelo.fr',
            'phone' => '0101010101',
        ];

        $this->json('PATCH', '/api/users/'.$currentUserId, $validPayload)->assertStatus(409);
    }


}
