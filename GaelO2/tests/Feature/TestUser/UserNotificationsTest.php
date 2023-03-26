<?php

namespace Tests\Feature\TestUser;

use Tests\TestCase;
use App\Models\User;
use App\Notifications\AutoImportPatients;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class UserNotificationsTest extends TestCase
{
    use RefreshDatabase;
    private int $userId;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $users = User::find(1);
        $users->notify(new AutoImportPatients("AutoImportReport","patientCreated"));
        $users->notify(new AutoImportPatients("AutoImportReport","patientCreated2"));
        $this->userId = $users->id;
    }

    public function testGetUserNotifications()
    {
        AuthorizationTools::logAsUser(1);
        $answer = $this->json('GET', '/api/users/' . $this->userId . '/notifications');
        $content= json_decode($answer->content(), true);
        $this->assertEquals(2, sizeof($content));
    }

    public function testGetUserNotificationsOnlyUnread()
    {
        AuthorizationTools::logAsUser(1);
        User::find($this->userId)->notifications->first()->markAsRead();
        $answer = $this->json('GET', '/api/users/' . $this->userId . '/notifications?unread');
        $content= json_decode($answer->content(), true);
        $this->assertEquals(1, sizeof($content));
    }

    public function testGetUserNotificationShouldFailWrongUser(){
        AuthorizationTools::logAsUser(1);
        $this->json('GET', '/api/users/' . 2 . '/notifications')->assertForbidden();
    }
}
