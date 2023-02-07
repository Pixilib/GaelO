<?php

namespace Tests\Feature\TestUser;

use App\GaelO\Constants\Enums\JobEnum;
use Tests\TestCase;
use App\Models\User;
use App\Models\Center;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class ModifyUserTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $center = Center::factory()->create();
        $this->user = User::factory()->job(JobEnum::SUPERVISION->value)->create();

        $this->validPayload = [
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'email' => 'test@test.fr',
            'phone' => '+33685969895',
            'administrator' => true,
            'centerCode' => $center->code,
            'job' => 'CRA',
            'orthancAddress'=> 'https://gaelo.fr',
            'orthancLogin'=>'gaelo',
            'orthancPassword'=>'gaelo'
        ];
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testValidModify()
    {
        AuthorizationTools::actAsAdmin(true);
        //Save database state before update
        $beforeChangeUser = User::where('id', $this->user['id'])->get()->first()->toArray();
        //Update with update API, shoud be success
        $resp = $this->json('PUT', '/api/users/' . $this->user['id'], $this->validPayload)->assertSuccessful();
        //Save after update
        $afterChangeUser = User::where('id', $this->user['id'])->get()->first()->toArray();

        //Value expected to have changed
        $updatedArray = [
            'lastname', 'firstname', 'email', 'phone',
            'administrator', 'center_code', 'job', 'orthanc_address', 'orthanc_login', 'orthanc_password'
        ];
        //Check that key needed to be updated has been updated in database
        foreach ($updatedArray as $key) {
            $this->assertNotEquals($beforeChangeUser[$key], $afterChangeUser[$key]);
        }
        //Check value not supposed to change by edition
        $notUpdatedArray = [
            'password',
            'creation_date'
        ];
        foreach ($notUpdatedArray as $key) {
            $this->assertEquals($beforeChangeUser[$key], $afterChangeUser[$key]);
        }
    }

    public function testModifyForbiddenNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $this->json('PUT', '/api/users/' . $this->user['id'], $this->validPayload)->assertStatus(403);
    }

    public function testWrongEmailValue()
    {
        AuthorizationTools::actAsAdmin(true);
        $this->validPayload['email'] = 'wrong email';
        $this->json('PUT', '/api/users/' . $this->user['id'], $this->validPayload)
            ->assertStatus(400);
    }

    public function testUncompleteRequest()
    {
        AuthorizationTools::actAsAdmin(true);
        $mandatoryTags = ['email', 'job', 'centerCode', 'administrator'];
        foreach ($mandatoryTags as $tag) {
            unset($this->validPayload[$tag]);
            $this->json('PUT', '/api/users/' . $this->user['id'], $this->validPayload)->assertStatus(400);
        }
    }

    public function testUsingAlreadyUsedEmail()
    {
        AuthorizationTools::actAsAdmin(true);
        $this->validPayload['email'] = "administrator@gaelo.fr";
        $this->json('PUT', '/api/users/' . $this->user['id'], $this->validPayload)
            ->assertStatus(409);
    }

    public function testMakeAccountUnconfirmed()
    {
        AuthorizationTools::actAsAdmin(true);
        $this->json('PUT', '/api/users/' . $this->user['id'], $this->validPayload)
            ->assertStatus(200);
        $updatedUser = User::where('id', $this->user['id'])->first();
        $this->assertNull($updatedUser['email_verified_at']);
    }

    public function testModifyUserOnboarding()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        $this->json('POST', '/api/users/' . $userId . '/onboarding', ['onboardingVersion' => '1.0.0'])
            ->assertStatus(200);
    }

    public function testModifyUserOnboardingShouldPreventDowngrading()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        $user = User::find($userId);
        $user->onboarding_version = '1.0.0';
        $user->save();

        $this->json('POST', '/api/users/' . $userId . '/onboarding', ['onboardingVersion' => '0.9.0'])
            ->assertStatus(400);
    }

    public function testModifyUserOnboardingShouldforbiddenForAnoterUser()
    {
        AuthorizationTools::actAsAdmin(false);
        $this->json('POST', '/api/users/' . $this->user['id'] . '/onboarding', ['onboardingVersion' => '1.0.0'])
            ->assertStatus(403);
    }
}
