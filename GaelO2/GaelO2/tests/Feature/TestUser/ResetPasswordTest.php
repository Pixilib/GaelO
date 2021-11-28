<?php

namespace Tests\Feature\TestUser;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\User;

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

    public function testValidResetPassword()
    {
        $data = [
            'email' => 'administrator@gaelo.fr'
        ];
        $this->json('POST', 'api/tools/reset-password', $data)->assertStatus(200);
        $modifiedUser = User::where('email', 'administrator@gaelo.fr')->sole();
        $this->assertEquals( $modifiedUser['status'], Constants::USER_STATUS_UNCONFIRMED );
    }

    public function testWrongEmailResetPassword(){
        $data = [
            'email' => 'administrator2@gaelo.fr'
        ];
        $this->json('POST', 'api/tools/reset-password', $data)
        ->assertStatus(404);

    }

    public function testResetDeactivatedAccount(){

        $defaultUser = User::find(1);
        $defaultUser->delete();

        $data = [
            'email' => 'administrator@gaelo.fr'
        ];

        $this->json('POST', 'api/tools/reset-password', $data)->assertStatus(400);

    }
}
