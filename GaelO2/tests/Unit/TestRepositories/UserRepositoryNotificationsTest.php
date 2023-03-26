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

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->userRepository = App::make(UserRepository::class);
        $user= User::factory()->create();
        $user->notify(new AutoImportPatients("patientCreated"));
    }
}