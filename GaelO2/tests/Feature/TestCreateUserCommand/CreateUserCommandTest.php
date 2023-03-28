<?php

namespace Tests\Feature\TestCreateUserCommand;

use App\GaelO\Constants\Enums\JobEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function testDeleteCommandShouldFailWrongStudyNameConfirmation()
    {
        $this->artisan('gaelo:create-user')
            ->expectsQuestion('email', 'test@test.fr')
            ->expectsQuestion('lastname', 'kanoun')
            ->expectsQuestion('firstname', 'salim')
            ->expectsQuestion('phone', '1234567890')
            ->expectsQuestion('Main Center Code', 0)
            ->expectsQuestion('Job', JobEnum::CRA->value)
            ->expectsQuestion('Password', 'azerty')
            ->expectsQuestion('Set Administrator role ?', "\r\n")
            ->assertExitCode(0);
    }

}
