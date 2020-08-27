<?php

namespace Tests\Feature;

use App\GaelO\UseCases\Login\LoginRequest;
use App\GaelO\UseCases\Login\LoginResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoginUseCase extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        /*
        $this->partialMock(\App\GaelO\UseCases\Login\Login::class, function ($mock) {
            $mock->shouldReceive('writeBlockedAccountInTracker')->andReturn(true);
        });
        */
        $loginRequest = new LoginRequest();
        $loginRequest->username = "administrator";
        $loginRequest->password = "administrator";

        $loginResponse = new LoginResponse();



    }
}
