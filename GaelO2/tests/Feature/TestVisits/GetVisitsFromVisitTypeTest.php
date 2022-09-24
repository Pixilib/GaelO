<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use Tests\TestCase;
use App\Models\Visit;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class GetVisitsFromVisitTypeTest extends TestCase
{

    use RefreshDatabase; 
    
    protected function setUp() : void {
        parent::setUp();
        $this->artisan('db:seed');
        $study = Study::factory()->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->create();
        $visitType = VisitType::factory()->visitGroupId($visitGroup->id)->count(2)->create();

        $visitType->each(function($visitType, $key) use ($study) {
            $visit = Visit::factory()->visitTypeId($visitType->id)->create();
            ReviewStatus::factory()->studyName($study->name)->visitId($visit->id)->create();
        });

        $this->studyName = $study->name;
        $this->visitTypeId = $visitType->first()->id;

    }


    public function testGetVisitFromVisitType(){

        $currentUserId = AuthorizationTools::actAsAdmin(false);

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $answer = $this->json('GET', 'api/studies/'.$this->studyName.'/visits?visitType='.$this->visitTypeId);
        $answer->assertStatus(200);
    }

    public function testGetVisitFromVisitTypeShouldFailNotSupervisor(){

        AuthorizationTools::actAsAdmin(false);
        $answer = $this->json('GET', 'api/studies/'.$this->studyName.'/visits?visitType='.$this->visitTypeId);
        $answer->assertStatus(403);
    }

}
