<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
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

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testResetPassword()
    {
        $data = [
            'username' => 'administrator',
            'email' => 'administrator@gaelo.fr'
        ];
        $this->post('api/tools/reset-password', $data)
        ->assertStatus(200);
    }
}
