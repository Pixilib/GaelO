<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use App\Model\Study;
use App\Model\VisitGroup;
use App\Model\VisitType;
use App\Model\Patient;
use App\Model\User;
use App\Model\Visit;
use Laravel\Passport\Passport;
use Tests\AuthorizationTools;
use Tests\TestCase;

class CorrectiveActionTest extends TestCase
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

    protected function setUp() : void{
        parent::setUp();
        Artisan::call('passport:install');

        Passport::actingAs(
            User::where('id',1)->first()
        );

        $this->study = factory(Study::class)->create(['patient_code_prefix' => 1234]);
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => $this->study->name]);
        $this->visitType = factory(VisitType::class)->create(['local_form_needed'=> true, 'visit_group_id' => $this->visitGroup->id]);
        $this->patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => $this->study->name, 'center_code' => 0]);

        $this->visit = factory(Visit::class)->create([
            'creator_user_id' => 1,
            'upload_status' => Constants::UPLOAD_STATUS_DONE,
            'patient_code' => $this->patient->code,
            'visit_type_id' => $this->visitType->id,
            'state_quality_control'=> Constants::QUALITY_CONTROL_CORRECTIVE_ACTION_ASKED,
            'state_investigator_form'=>Constants::INVESTIGATOR_FORM_DONE
        ]);


    }


    public function testCorrectiveAction()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $payload = [
            'newSeriesUploaded' => true,
            'newInvestigatorForm'=>true,
            'correctiveActionDone'=>true,
            'comment' => "lala"
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/corrective-action', $payload);
        $response->assertStatus(200);
    }

    public function testCorrectiveActionShouldFailNoRole()
    {
        $payload = [
            'newSeriesUploaded' => true,
            'newInvestigatorForm'=>true,
            'correctiveActionDone'=>true,
            'comment' => "lala"
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/corrective-action', $payload);
        $response->assertStatus(403);
    }

    public function testCorrectiveActionShouldFailCorrectiveActionNotAsked()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $this->visit->state_quality_control = Constants::QUALITY_CONTROL_NOT_DONE;
        $this->visit->save();
        $payload = [
            'newSeriesUploaded' => true,
            'newInvestigatorForm'=>true,
            'correctiveActionDone'=>true,
            'comment' => "lala"
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/corrective-action', $payload);
        $response->assertStatus(403);
    }

    public function testCorrectiveActionShouldFailFormMissing()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $this->visit->state_investigator_form = Constants::INVESTIGATOR_FORM_NOT_DONE;
        $this->visit->save();
        $payload = [
            'newSeriesUploaded' => true,
            'newInvestigatorForm' => true,
            'correctiveActionDone'=>true,
            'comment' => "lala"
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/corrective-action', $payload);
        $response->assertStatus(403);
    }
}
