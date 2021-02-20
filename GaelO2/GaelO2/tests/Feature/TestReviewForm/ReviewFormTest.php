<?php

namespace Tests\Feature\TestReviewForm;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;

class ReviewFormTest extends TestCase
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
    }

    public function testCreateReviewForm(){
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::Factory()->patientCode($patient->code)->visitTypeId($visitType->id)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $study->name);

        $payload = [
            'data' => ['lugano' => 'CR'],
            'validated' => true
        ];

        dd($this->post('api/studies/'.$study->name.'/visits/'.$visit->id.'/reviews', $payload));
        //->assertStatus(201);



    }


}
