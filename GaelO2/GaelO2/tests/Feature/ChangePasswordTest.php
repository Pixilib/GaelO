<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\User;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;

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



    protected function setUp() : void{
        parent::setUp();
        $user = factory(User::class)->create(
            ['password' => Hash::make('password12345'),
            'status' => 'Activated',
            'password_temporary'=>Hash::make('temporaryPassword'),
            'password_previous1' => Hash::make('previousPassword1'),
            'password_previous2' => Hash::make('previousPassword2')]);

        $this->validPayload = [
            'previous_password' => 'password12345',
            'password1'=>'newPassword12345',
            'password2'=>'newPassword12345'
        ];
        $this->user = $user;

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',$user->id)->first()
        );

    }

    public function testChangePassword()
    {
        $this->json('PUT', '/api/users/'.$this->user['id'].'/password', $this->validPayload)->assertNoContent(200);

    }

    public function testChangePasswordFromNonSameUser()
    {
        //Should be unauthorized
        $this->json('PUT', '/api/users/1/password', $this->validPayload)->assertNoContent(403);

    }

    public function testChangePasswordWrongNewPasswordCopy()
    {
        $this->validPayload['password2']='wrongCopy';
        $response = $this->json('PUT', '/api/users/'.$this->user['id'].'/password', $this->validPayload);
        $response->assertStatus(400);

    }

    public function testChangePasswordWrongCurrentPassword()
    {
        $this->validPayload['previous_password']='wrongPassword';
        $response = $this->json('PUT', '/api/users/'.$this->user['id'].'/password', $this->validPayload);
        $response->assertStatus(400);

    }

    public function testChangePasswordWrongLength()
    {
        $this->validPayload['password1']='short';
        $this->validPayload['password2']='short';
        $response = $this->json('PUT', '/api/users/'.$this->user['id'].'/password', $this->validPayload);
        $response->assertStatus(400);

    }

    public function testChangePasswordNoDigit()
    {
        $this->validPayload['password1']='LongPassWithNoDigit';
        $this->validPayload['password2']='LongPassWithNoDigit';
        $response = $this->json('PUT', '/api/users/'.$this->user['id'].'/password', $this->validPayload);
        $response->assertStatus(400);

    }


    public function testChangePasswordNoLetter()
    {
        $this->validPayload['password1']='LongPassWithNoDigit';
        $this->validPayload['password2']='LongPassWithNoDigit';
        $response = $this->json('PUT', '/api/users/'.$this->user['id'].'/password', $this->validPayload);
        $response->assertStatus(400);

    }
    public function testChangePasswordAllSameCase()
    {
        $this->validPayload['password1']='azertyuio';
        $this->validPayload['password2']='azertyuio';
        $response = $this->json('PUT', '/api/users/'.$this->user['id'].'/password', $this->validPayload);
        $response->assertStatus(400);

    }

    public function testChangePasswordNotAllowedCharacter()
    {
        $this->validPayload['password1']='newPassword12345.';
        $this->validPayload['password2']='newPassword12345.';
        $response = $this->json('PUT', '/api/users/'.$this->user['id'].'/password', $this->validPayload);
        $response->assertStatus(400);

    }

        public function testChangePasswordSameCurrent()
    {
        $this->validPayload['password1']='password12345';
        $this->validPayload['password2']='password12345';
        $response = $this->json('PUT', '/api/users/'.$this->user['id'].'/password', $this->validPayload);
        $response->assertStatus(400);

    }

    public function testChangePasswordSamePrevious1()
    {
        $this->validPayload['password1']='previousPassword1';
        $this->validPayload['password2']='previousPassword1';
        $response = $this->json('PUT', '/api/users/'.$this->user['id'].'/password', $this->validPayload);
        $response->assertStatus(400);

    }

    public function testChangePasswordSamePrevious2()
    {
        $this->validPayload['password1']='previousPassword2';
        $this->validPayload['password2']='previousPassword2';
        $response = $this->json('PUT', '/api/users/'.$this->user['id'].'/password', $this->validPayload);
        $response->assertStatus(400);

    }

    public function testChangePasswordNoPreviousPassword(){
        $this->user['password_previous1'] = null;
        $this->user['password_previous2'] = null;
        $this->user['last_password_update'] = now()->subDays(100);
        $this->user->save();
        $this->json('PUT', '/api/users/'.$this->user['id'].'/password', $this->validPayload)->assertNoContent(200);
    }

    public function testChangePasswordNoCurrentPassword(){
        $this->user['password'] = null;
        $this->user['password_previous1'] = null;
        $this->user['password_previous2'] = null;
        $this->user['status'] = Constants::USER_STATUS_UNCONFIRMED;
        $this->user->save();
        $this->validPayload['previous_password']='temporaryPassword';
        $this->json('PUT', '/api/users/'.$this->user['id'].'/password', $this->validPayload)->assertNoContent(200);
    }

}
