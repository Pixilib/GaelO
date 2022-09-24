<?php

namespace Tests\Feature\TestUser;

use Tests\TestCase;
use App\Models\User;
use App\Models\Center;
use App\Models\CenterUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class AffiliatedCenterTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp() : void{
        parent::setUp();
        $this->artisan('db:seed');
        Center::factory()->code(3)->create();
        Center::factory()->code(4)->create();

        $this->payload = [
            'centerCode' => 3
        ];

    }

    public function testCreateAffiliatedAccessShouldFailForNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $this->json('POST', 'api/users/1/affiliated-centers', $this->payload)->assertStatus(403);
    }

    public function testGetAffiliatedAccessShouldFailForNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', 'api/users/1/affiliated-centers')->assertStatus(403);

    }

    public function testDeleteAffiliatedCenterShouldFailForNotAdmin(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addAffiliatedCenter($currentUserId, 3);
        $this->json('DELETE', 'api/users/1/affiliated-centers/3')->assertStatus(403);
    }

    public function testCreateAffiliatedCenterToUser()
    {
        AuthorizationTools::actAsAdmin(true);
        $this->json('POST', 'api/users/1/affiliated-centers', $this->payload)->assertStatus(201);

        $affiliatedCenter =User::where('id',1)->first()->affiliatedCenters()->get()->toArray();
        $this->assertEquals(sizeof($affiliatedCenter), 1);
        $this->assertEquals($affiliatedCenter[0]['code'], 3);
    }

    public function testCreateAlreadyExistingAffiliatedCenterToUser(){

        AuthorizationTools::actAsAdmin(true);
        AuthorizationTools::addAffiliatedCenter(1, 3);

        $this->json('POST', 'api/users/1/affiliated-centers', $this->payload)->assertStatus(409);

        $affiliatedCenter =User::where('id',1)->first()->affiliatedCenters()->get()->toArray();
        $this->assertEquals(sizeof($affiliatedCenter), 1);

    }

    public function testGetAffiliatedCenterOfUser(){
        AuthorizationTools::actAsAdmin(true);
        AuthorizationTools::addAffiliatedCenter(1, 3);
        $response = $this->json('GET', 'api/users/1/affiliated-centers')->assertSuccessful();
        $response = json_decode($response->content(), true);
        $this->assertEquals(sizeof($response), 1);

    }

    public function testDeleteAffiliatedCenterOfUser(){
        AuthorizationTools::actAsAdmin(true);
        AuthorizationTools::addAffiliatedCenter(1, 3);
        $this->json('DELETE', 'api/users/1/affiliated-centers/3')->assertNoContent(200);
        $databaseData = CenterUser::where(['user_id'=>1])->get()->toArray();
        $this->assertEquals(sizeof($databaseData), 0);
    }

    public function testDeleteAffiliatedCenterOfUserShouldFailBecauseNotExistingAffiliatedCenter(){
        AuthorizationTools::actAsAdmin(true);
        $this->json('DELETE', 'api/users/1/affiliated-centers/3')->assertStatus(404);
    }

    public function testGetUsersCenters(){
        //Should pass for admin
        AuthorizationTools::actAsAdmin(true);
        $this->json('GET', 'api/users/1/centers')->assertStatus(200);
        //Should Pass for same user
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addAffiliatedCenter($currentUserId, 3);
        $this->json('GET', 'api/users/'.$currentUserId.'/centers')->assertStatus(200);

    }

    public function testGetUsersCenterShouldFailNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', 'api/users/1/centers')->assertStatus(403);
    }
}
