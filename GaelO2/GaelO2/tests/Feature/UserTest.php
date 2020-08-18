<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testGetUser()
    {
        $user = factory(App\User::class, 10)->create();
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
