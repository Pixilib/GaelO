<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;

use Illuminate\Foundation\Testing\DatabaseMigrations;

class ChangePasswordTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    public function testChangePassword()
    {
        $user = factory(User::class)->create(['password' => 'Ceciest1test', 'status' => 'Activated', 'password_previous1' => 'Cecietait1test']);
        $data = [
            'id' => 2,
            'previous_password' => 'Ceciest1test',
            'password1' => 'Ceciest1nveautest',
            'password2' => 'Ceciest1nveautest'
        ];

        //Test data correctly updated
        //$response = $this->json('PUT', '/api/users/'+$data['id']+'/password', $data);
        //dd($response);
        //Test password format incorrect
        $data['password1'] = 'test';
        $data['password2'] = $data['password1'];
        //$response = $this->json('PUT', '/api/users/'+$data['id'], $data) -> assertStatus(400);
        //$response -> dump();
        //Test two passwords do not match
        $data['password2'] = 'CeciEst1nveautest';
        //$response = $this->json('PUT', '/api/users'+$data['id'], $data) -> assertStatus(400);
        //Test previously used password
        $data['password1'] = 'Cecietait1test';
        $data['password2'] = $data['password1'];
        //$response = $this->json('PUT', '/api/users'+$data['id'], $data) -> assertStatus(400);

    }
}
