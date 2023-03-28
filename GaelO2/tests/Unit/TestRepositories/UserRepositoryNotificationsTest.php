<?php

namespace Tests\Unit\TestRepositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Models\Study;
use App\Models\Center;
use App\GaelO\Repositories\UserRepository;
use App\Models\User;
use App\Notifications\AutoImportPatients;
use Illuminate\Support\Facades\App;

class UserRepositoryNotificationsTest extends TestCase
{
    private UserRepository $userRepository;
    private int $userId;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->userRepository = App::make(UserRepository::class);
        $users = User::factory()->count(5)->create();
        $users->first()->notify(new AutoImportPatients("AutoImportReport","patientCreated"));
        $users->first()->notify(new AutoImportPatients("AutoImportReport","patientCreated2"));
        $users->first()->notify(new AutoImportPatients("AutoImportReport","patientCreated3"));
        $users->first()->notify(new AutoImportPatients("AutoImportReport","patientCreated4"));
        $this->userId = $users->first()->id;
    }

    public function testGetUserNotifications()
    {
        $notifications = $this->userRepository->getUserNotifications($this->userId, false);
        $this->assertEquals(4, sizeof($notifications));
    }

    public function testGetUserNotificationsOnlyUnread()
    {
        User::find($this->userId)->notifications->first()->markAsRead();
        $notifications = $this->userRepository->getUserNotifications($this->userId, true);
        $this->assertEquals(3, sizeof($notifications));
    }

    public function testMakeUserNotificationsRead()
    {
        $notifications = User::find($this->userId)->notifications;
        $this->userRepository->markUserNotificationsRead($this->userId, [$notifications->first()->id, $notifications->last()->id]);
        $unreadNotifications = User::find($this->userId)->unreadNotifications;
        $this->assertEquals(2, count($unreadNotifications));
    }

    public function testDeleteUserNotifications(){
        $notifications = User::find($this->userId)->notifications;
        $this->userRepository->deleteUserNotifications($this->userId, [$notifications->first()->id]);
        $notifications = User::find($this->userId)->notifications;
        $this->assertEquals(3, count($notifications));
    }
}
